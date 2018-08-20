<?php
/*

    This file is part of the DuckFusion Framework.

    This is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    DuckFusion Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with DuckFusion Framework.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 *
 * Quack
 * 
 * This class contains the rendering engine "Quack" which is used by default to render template files
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

// Issues
// - Issues with delim regexs. If we have the /U flag, then we can have multiple on same line, e.g. {echo 'sup again'} {echo 'sup now'}, but then it fucks up with double and triple delims
// - If we don't have the /U flag, then the other delims work, but we get a parse error if we have multiple on same line, as it's wrapping to first and last
// - Will leave with /U flag for now, but needs fixing
// - [[noparse]] doesn't work properly, if you are inside a tag and you are parsing the end of that tag, e.g. [[section:a]][[noparse]][[section:b]]hi[[endsection]][[endnoparse]][[endsection]]
// - switch case doesn't work unless first case is at the beginning of line. Any spaces or tabs cause an error. Need to strip any whitespace between them.
// - If statements don't work inline, if there are braces () inside the condition, e.g. @if(true) {{something()}} @endif

namespace DF;

use DF\Renderer;

class Quack extends Renderer {
        
    private $sections = array();
    private $noParses = array();
    private $inSwitch = false;
    
    private static $controls = array(
        'use',
        'import',
        'noparse',
        'section'
    );
    
    const DELIM = "|";
    const DELIM_OPTION = ":";
    
    // Controls
    const REGEX_USE = "/\[\[use\:(.*?)\]\]/";
    const REGEX_SECTION = "/\[\[section\:([a-z0-9]+)\]\](.*?)\[\[endsection\]\]/s";
    const REGEX_IMPORT = "/\[\[import\:(.*?)\]\]/";
    const REGEX_NOPARSE = "/\[\[noparse\]\](.*?)\[\[endnoparse\]\]/s";

    // Conditionals
    const REGEX_IF = "/@if(\W)?\((.*?)\)/U";
    const REGEX_ENDIF = "/@endif/";
    const REGEX_ELSE = "/@else/";
    const REGEX_ELSEIF = "/@elseif(\W)?\((.+?)\)/U"; // changed a lot of those from (.*?) to (.+?)
    const REGEX_CASE_SWITCH = "/@scase\W?\((.+?)\)/U"; // This is a short/simple switch case, for when you only want to echo simple things
    // TODO: Proper Switch Case
    const REGEX_SWITCH = "/@switch(\W)?\((.+?)\)/U";
    const REGEX_SWITCH_CASE = "/@case(\W)?\((.+?)\)/U";
    const REGEX_SWITCH_DEFAULT = "/@default/";
    const REGEX_ENDSWITCH = "/@endswitch/";
    
    // Loops
    const REGEX_FOREACH = "/@foreach(\W)?\((.+?)\)/U";
    const REGEX_ENDFOREACH = "/@endforeach/";
    const REGEX_FOR = "/@for(\W)?\((.+?)\)/U";
    const REGEX_ENDFOR = "/@endfor/";
    const REGEX_WHILE = "/@while(\W)?\((.+?)\)/U";
    const REGEX_ENDWHILE = "/@endwhile/";
    const REGEX_EACH = "/@each\s?\((.*?) in ((?:\\$)?[a-z0-9]+)(\.{2,3})((?:\\$)?[a-z0-9]+)( step (.+?))?\)/i";
    const REGEX_ENDEACH = "/@endeach/";
    
    const REGEX_CONTINUE = "/@continue/";
    const REGEX_BREAK = "/@break/";
    
    const REGEX_3_DELIM = "/\{{3}(.*?)\}{3}/";
    const REGEX_2_DELIM = "/\{{2}(.*?)\}{2}/";
    const REGEX_1_DELIM = "/(?<![\\\\])\{{1}(.*?)(?<![\\\\])\}{1}/";
    
    const REGEX_COMMENT = "/\{+\*(.*?)\*\}+/";

   
    
    /**
     * Parse the content
     */
    public function parse($content){

                
        // Parse for any sections to be taken out and put elsewhere
        $this->parseSection($content);
        
        // Then parse for a layout to use
        $this->parseUse($content);
        
        // Then parse for the import of those sections elsewhere in the template
        $this->parseImport($content);
        
        // Remove any comments
        $this->parseComments($content);
        
        // Then remove any elements we don't want to parse, so we can add them back in as they were at the end
        $this->parseNoParse($content);
                
        
        // Then parse for if statements
        $this->parseIf($content);
        $this->parseEndIf($content);
        $this->parseElseIf($content);
        $this->parseElse($content);
        
        // Then parse for switch case statements
        $this->parseSwitch($content);
        $this->parseEndSwitch($content);
        $this->parseSwitchCase($content);
        $this->parseSwitchDefault($content);
        
        $this->parseCaseSwitch($content); // Short/Simple version
        
        
        
        // Then parse for foreach loops
        $this->parseForEach($content);
        $this->parseEndForEach($content);
        
        // Then parse for for loops
        $this->parseFor($content);
        $this->parseEndFor($content);
        
        // Then parse for while loops
        $this->parseWhile($content);
        $this->parseEndWhile($content);
        
        // Then parse for range loops (each)
        $this->parseEach($content);
        $this->parseEndEach($content);
        
        
        // Then parse for continue
        $this->parseContinue($content);
        $this->parseBreak($content);
        
        
        
        
        
        
        // Then parse for triple {delims} to convert to htmlentities echo
        $this->parseTripleDelims($content);
        
        // Then parse for double {delims} to conver to an echo
        $this->parseDoubleDelims($content);
        
        // Then parse for single {delims} to convert to PHP code
        $this->parseSingleDelims($content);
        
        $this->removeEscapedDelimiters($content);
        
        // Now add back in the noparse bits we removed
        $this->returnNoParse($content);
        
        
        return $content;
        
    }
    
    /**
     * Parse any comments in the template, so they are are ignored
     * @param type $content
     */
    protected function parseComments(&$content){
        
        // Check for a layout
        if (preg_match_all(self::REGEX_COMMENT, $content, $matches)){
                                    
            if ($matches){
                
                foreach($matches[0] as $match){
                    
                    $content = str_replace($match, "", $content);
                    
                }
                
            }
                        
        }
        
    }
    
    /**
     * Parse the [[use:x]] tag and include the relevant tpl file to be used
     * @param type $content
     */
    protected function parseUse(&$content){
                
        // Check for a layout
        if (preg_match(self::REGEX_USE, $content, $matches)){
                                    
            if (file_exists( df_APP_ROOT . df_DS . 'views' . df_DS . $matches[1] . '.tpl.html' )){
                
                // Remove it from the template
                $content = preg_replace("/\[\[use:(.*?)\]\]/", "", $content);
                
                // Load the layout
                $content = file_get_contents( df_APP_ROOT . df_DS . 'views' . df_DS . $matches[1] . '.tpl.html' );
                
            }
                        
        }
        
    }
    
    /**
     * Parse the content for [[section]] to remove them from the content and put them wherever they are imported instead
     * @param type $content
     */
    protected function parseSection(&$content){
                
        // Check for any sections
        if (preg_match_all(self::REGEX_SECTION, $content, $matches)){
                        
            $cnt = count($matches[0]);
                        
            // Loop through the sections
            for ($i = 0; $i < $cnt; $i++)
            {
                
                $name = $matches[1][$i];
                $body = $matches[2][$i];
                $this->sections[$name] = trim($body);
                
            }
            
            // Now remove them from the actual content
            $content = preg_replace(self::REGEX_SECTION, "", $content);
            
        }
                        
    }
    
    /**
     * Parse the [[import:x]] tag and replace with the relevant section
     * @param type $content
     */
    protected function parseImport(&$content){
                       
        if (preg_match_all(self::REGEX_IMPORT, $content, $matches)){
                        
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $import = $matches[0][$i];
                $name = $matches[1][$i];
                
                // If we have that section put it here instead
                if (isset($this->sections[$name])){
                    $content = str_replace($import, $this->sections[$name], $content);
                }
                
            }
                        
        }
                
    }
    
     /**
     * Parse the content for [[noparse]] to remove them from the content so they don't get parsed by the rest
     * @param type $content
     */
    protected function parseNoParse(&$content){
                
        // Check for any sections
        if (preg_match_all(self::REGEX_NOPARSE, $content, $matches)){
            
            $cnt = count($matches[0]);
                        
            // Loop through the sections
            for ($i = 0; $i < $cnt; $i++)
            {
                
                $noParse = $matches[0][$i];
                $body = $matches[1][$i];
                $this->noParses[$i] = trim($body);
                
                // Remove and put a placeholder in
                $content = str_replace($noParse, "__NOPARSEPLACEHOLDER[{$i}]__", $content);
                
            }
            
        }
                
    }
    
    /**
     * Put the sections we removed back in without having been parsed
     * @param type $content
     */
    protected function returnNoParse(&$content){
                
        if (preg_match_all("/__NOPARSEPLACEHOLDER\[(\d)\]__/", $content, $matches)){
            
            $cnt = count($matches[0]);
                        
            for ($i = 0; $i < $cnt; $i++)
            {
                $content = str_replace("__NOPARSEPLACEHOLDER[{$i}]__", $this->noParses[$i], $content);                
            }
                        
        }
        
    }
    
    /**
     * Parse an if statement
     * @param type $content
     */
    protected function parseIf(&$content){
        
        if (preg_match_all(self::REGEX_IF, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php if({$condition}): ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Parse the end of an if statement
     * @param type $content
     */
    protected function parseEndIf(&$content){
        
        if (preg_match_all(self::REGEX_ENDIF, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php endif; ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Parse an elseif statement
     * @param type $content
     */
    protected function parseElseIf(&$content){
        
        if (preg_match_all(self::REGEX_ELSEIF, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php elseif({$condition}): ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Parse an else statement
     * @param type $content
     */
    protected function parseElse(&$content){
        
        if (preg_match_all(self::REGEX_ELSE, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php else: ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert a @switch to a php switch case block
     * @param type $content
     */
    protected function parseSwitch(&$content){
        
        if (preg_match_all(self::REGEX_SWITCH, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php switch({$condition}): ?>", $content);
                
            }
            
        }
        
    }
    
    
    /**
     * Convert a @switch to a php switch case block
     * @param type $content
     */
    protected function parseSwitchCase(&$content){
        
        if (preg_match_all(self::REGEX_SWITCH_CASE, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php case {$condition}: ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert a @default to a php default for a switch case
     * @param type $content
     */
    protected function parseSwitchDefault(&$content){
        
        if (preg_match_all(self::REGEX_SWITCH_DEFAULT, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php default: ?>", $content);
                
            }
            
        }
        
    }
    
    
    /**
     * Convert an @endswitch to a php endswitch
     * @param type $content
     */
    protected function parseEndSwitch(&$content){
        
        if (preg_match_all(self::REGEX_ENDSWITCH, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php endswitch; ?>", $content);
                
            }
            
        }
        
    }
    
    
    
    /**
     * Convert a @foreach to a php foreach loop
     * @param type $content
     */
    protected function parseForEach(&$content){
        
        if (preg_match_all(self::REGEX_FOREACH, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php foreach({$condition}): ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert an @endforeach to a php endforeach
     * @param type $content
     */
    protected function parseEndForEach(&$content){
        
        if (preg_match_all(self::REGEX_ENDFOREACH, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php endforeach; ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert a @for to a php for statement
     * @param type $content
     */
    protected function parseFor(&$content){
        
        if (preg_match_all(self::REGEX_FOR, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php for({$condition}): ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Parse an @endfor to a php endfor statement
     * @param type $content
     */
    protected function parseEndFor(&$content){
        
        if (preg_match_all(self::REGEX_ENDFOR, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php endfor; ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Parse a @while to a php while statement
     * @param type $content
     */
    protected function parseWhile(&$content){
        
        if (preg_match_all(self::REGEX_WHILE, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $condition = $matches[2][$i];
                
                $content = str_replace($string, "<?php while({$condition}): ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert a @endwhile to a php endwhile
     * @param type $content
     */
    protected function parseEndWhile(&$content){
        
        if (preg_match_all(self::REGEX_ENDWHILE, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php endwhile; ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert an @each(v in a..b step c) to a php foreach loop
     *      Example:
     *          @each ($i in 1..15 step 3)
     *      This creates a range between 1 and 15, stepping by 3 each time, and each iteration through the loop the current value can be referenced by the variable $i
     *
     * Two dots in the range '..' is inclusive, three dots '...' excludes the last element.
     *      Example:
     *      1..5 = 1, 2, 3, 4, 5 (same as saying while <= 5)
     *      1...5 = 1, 2, 3, 4 (same as saying while < 5)
     * 
     * @param type $content
     */
    protected function parseEach(&$content){
        
        // Each without a step
        if (preg_match_all(self::REGEX_EACH, $content, $matches)){
            
            $cnt = count($matches[0]);

            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $var = $matches[1][$i];
                $from = $matches[2][$i];
                $dots = $matches[3][$i];
                $to = $matches[4][$i];
                $step = !df_empty($matches[6][$i]) ? $matches[6][$i] : 1;
                
                if (strlen($dots) == 3){
                    $to--;
                }
                                                              
                $content = str_replace($string, "<?php foreach( range({$from}, {$to}, {$step}) as {$var} ): ?>", $content);
                                
            }
            
        }
        
        
        
    }
    
    
    /**
     * Convert an @endforeach to a php endforeach
     * @param type $content
     */
    protected function parseEndEach(&$content){
        
        if (preg_match_all(self::REGEX_ENDEACH, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php endforeach; ?>", $content);
                
            }
            
        }
        
    }
    
    
    
    /**
     * This is for a short switch case, where you only want to echo something out depending on the case
     * Format:
     *      @case($d, 4=>'four', 5=>'five', 'dunno')
     *      The switch is done on $d, the cases are: 4, 5 and default. If 4, it echoes 'four', if 5 it echoes 'five', default it echoes 'dunno'
     * @param type $content
     */
    protected function parseCaseSwitch(&$content){
                
        if (preg_match_all(self::REGEX_CASE_SWITCH, $content, $matches)){
                                    
            $tmp = "";
            $cnt = count($matches[0]);
                                    
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $conditions = $matches[1][$i];
                $options = str_getcsv($conditions, ",");
     
                if (count($options) > 1)
                {
                    
                    $switch = array_shift($options);
                    $tmp .= "<?php switch({$switch}): ";
                    
                    $first = true;
                    
                    foreach($options as $option)
                    {
                        
                        $pieces = explode("=>", $option);
                        
                        if (!$first)
                        {
                            $tmp .= "<?php ";
                        }
                        
                        $first = false;
                        
                        
                        // Default
                        if (count($pieces) == 1)
                        {
                            $then = trim($pieces[0]);
                            $tmp .= "default: echo {$then}; break; ?>";
                        }
                        else
                        {
                            $cond = trim($pieces[0]);
                            $then = trim($pieces[1]);
                            $tmp .= "case {$cond}: echo {$then}; break; ?>";
                        }
                        
                        
                        
                    }
                    
                    $tmp .= "<?php endswitch; ?>";
                    
                }
                                                
                $content = str_replace($string, $tmp, $content);
                
            }
            
            
        }
        
    }
    
    /**
     * Convert @continue to a php continue
     * @param type $content
     */
    protected function parseContinue(&$content){
        
        if (preg_match_all(self::REGEX_CONTINUE, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php continue; ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Convert @break to a php break
     * @param type $content
     */
    protected function parseBreak(&$content){
        
        if (preg_match_all(self::REGEX_BREAK, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $content = str_replace($string, "<?php break; ?>", $content);
                
            }
            
        }
        
    }
    
    
    
    /**
     * ECho out whatever is in the delims, and first run it through df_html()
     * @param type $content
     */
    protected function parseTripleDelims(&$content){
        
        if (preg_match_all(self::REGEX_3_DELIM, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $body = $matches[1][$i];
                
                // Do we have any modifiers on the text/variable?
                if (strpos($body, self::DELIM) !== false){
                    
                    $modifiers = preg_split("/(?<!\\\\)\\".self::DELIM."/", $body);
                    $body = $modifiers[0];
                    
                    $n = 0;
                    
                    if ($modifiers){
                        
                        foreach($modifiers as $mod){
                            
                            $n++;
                            
                            if ($n == 1) continue;
                            
                            // Any options?
                            $options = explode(":", $mod);
                            if ($options){
                                $mod = array_shift($options);
                            }
                                                        
                            $this->applyModifier($mod, $body, $options);
                            
                        }
                        
                    }
                    
                }
                
                $content = str_replace($string, "<?= df_html({$body}) ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Echo out whatever is in the delims
     * @param type $content
     */
    protected function parseDoubleDelims(&$content){
        
        if (preg_match_all(self::REGEX_2_DELIM, $content, $matches)){
            
            $cnt = count($matches[0]);
                        
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $body = $matches[1][$i];
                
                // Do we have any modifiers on the text/variable?
                if (strpos($body, self::DELIM) !== false){
                    
                    
                    $modifiers = preg_split("/(?<!\\\\)\\".self::DELIM."/", $body);
                    $body = $modifiers[0];
                                        
                    $n = 0;
                    
                    if ($modifiers){
                        
                        foreach($modifiers as $mod){
                                                        
                            $n++;
                            
                            if ($n == 1) continue;
                            
                            // Any options?
                            $options = preg_split("/(?<!\\\\)\\".self::DELIM_OPTION."/", $mod);

                            if ($options){
                                $mod = array_shift($options);
                                
                                foreach($options as &$opt)
                                {
                                    $opt = str_replace("\\" . self::DELIM_OPTION, self::DELIM_OPTION, $opt);
                                }
                                
                            }
                                                        
                            $this->applyModifier($mod, $body, $options);
                            
                        }
                        
                    }
                    
                }
                
                $content = str_replace($string, "<?= {$body} ?>", $content);
                
            }
            
        }
        
    }
    
    /**
     * Simply convert to <?php ?> tags
     * @param type $content
     */
    protected function parseSingleDelims(&$content){
        
        if (preg_match_all(self::REGEX_1_DELIM, $content, $matches)){
            
            $cnt = count($matches[0]);
            
            for($i = 0; $i < $cnt; $i++)
            {
                
                $string = $matches[0][$i];
                $body = $matches[1][$i];
                
                $content = str_replace($string, "<?php {$body} ?>", $content);
                
            }

        }
        
    }
    
    /**
     * Remove any escaped "|" symbols
     * @param type $content
     */
    protected function removeEscapedDelimiters(&$content){
        
        $content = preg_replace("/\\\\\\".self::DELIM."/", self::DELIM, $content);
        
    }
    
    /**
     * Apply any extra modifiers to data from the single/double/triple delim output
     * @param type $mod
     * @param type $txt
     * @param type $options
     */
    protected function applyModifier($mod, &$txt, $options = false){
                                
        switch ($mod)
        {
            
            case 'e':
            case 'escape':
                $txt = "addslashes({$txt})";
            break;    
            
            case 'upper':
                $txt = "strtoupper({$txt})";
            break;
        
            case 'lower':
                $txt = "strtolower({$txt})";
            break;
        
            case 'reverse':
                $txt = "strrev({$txt})";
            break;
        
            case 'cycle':
                
                // If using an array
                if (is_array($txt)){
                    
                }
                // Else if using string with delim
                elseif (is_string($txt) && $options){
                    $name = (isset($options[0])) ? $options[0] : '';
                    $delim = (isset($options[1])) ? $options[1] : ',';
                    $txt = "string_cycle({$txt}, {$name}, {$delim})";
                }
                                
                
            break;
        
            case 'capitalise':
            case 'capitalize':
                                
                if ($options && in_array("all", $options)){
                    $txt = "ucwords({$txt})";
                } else {
                    $txt = "ucfirst({$txt})";
                }
            break;
            
            case 'chomp':
                
                if ($options)
                {

                    $limit = $options[0];
                    
                    if (isset($options[1]))
                    {
                        $ellipsis = $options[1];
                        $txt = "mb_strimwidth({$txt}, 0, {$limit}, {$ellipsis})";
                    }
                    else
                    {
                        $txt = "substr({$txt}, 0, {$limit})";
                    }
                    
                }
                
            break;
            
            case 'encode':
                
                if ($options)
                {
                    
                    // Detect encoding automatically and convert to this
                    if (count($options) == 1)
                    {
                        
                        $from = mb_detect_encoding($txt);
                        $to = $options[0];
                        $txt = "mb_convert_encoding({$txt}, '{$from}', {$to})";
                        
                    }
                    elseif (count($options) == 2)
                    {
                        
                        $from = $options[0];
                        $to = $options[1];
                        $txt = "mb_convert_encoding({$txt}, {$from}, {$to})";
                        
                    }

                    
                }
                
            break;
            
            case 'default':
                
                if ($options)
                {
                    
                    $cnt = count($options);
                    $n = 1;
                    
                    $var = $txt;
                    $default = $options[0];
                    $tmp = " ( (isset({$txt}) && !is_null({$txt}) && !\df_empty({$txt})) ? {$txt} : \n";
                    
                    foreach($options as $option)
                    {
                        
                        // if there are more to come
                        if ($cnt > $n)
                        {
                            
                            $tmp .= " ( (isset({$option}) && !is_null({$option}) && !\df_empty({$option})) ? {$option} : \n";
                            
                        }
                        else
                        {
                            $tmp .= "{$option}";
                            
                            for ($i = $cnt; $i > 1; $i--)
                            {
                                $tmp .= " ) ";
                            }
                            
                        }
                        
                        
                        $n++;
                        
                    }
                    
                    $tmp .= " ) ";
                                      
                    $txt = $tmp;
                    
                }
                
            break;
            
            case 'format':
                
                if ($options)
                {
                    
                    $txt = "sprintf({$txt}, ".implode(",", $options).")";
                    
                }
                
            break;
            
            case 'dump':
                
                $txt = "var_dump({$txt})";
                
            break;
        
            case 'implode':
            case 'join':
                
                $seperator = "', '";
                
                if ($options)
                {
                    $seperator = $options[0];
                }
                                
                $txt = "implode({$seperator}, {$txt})";
                
            break;
            
            case 'split':
                
                if ($options)
                {
                    $glue = $options[0];
                    $txt = "explode({$glue}, {$txt})";
                }
                
            break;
            
            case 'json':
                
                if ($options && $options[0] == 'decode')
                {
                    $txt = "json_decode({$txt})";
                }
                else
                {
                    $txt = "json_encode({$txt})";
                }
                
            break;
            
            case 'replace':
                
                if ($options)
                {
                    
                    $replace = $options[0];
                    $with = (isset($options[1])) ? $options[1] : '';
                    $txt = "str_replace({$replace}, {$with}, {$txt})";
                    
                }
                
            break;
            
            case 'wrap':
                
                if ($options)
                {
                    $limit = $options[0];
                    $char = (isset($options[1])) ? $options[1] : '"<br>\n"';
                    $txt = "wordwrap({$txt}, {$limit}, {$char})";
                    
                }
                
                
            break;
            
            
            case 'func':
                
                if ($options)
                {
                    
                    $func = $options[0];
                    if (function_exists($func))
                    {
                        $txt = "{$func}({$txt})";
                    }
                    
                }
                
            break;
            
        }
        
        
    }
    
}