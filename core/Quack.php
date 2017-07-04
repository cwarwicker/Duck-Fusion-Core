<?php
namespace DF;

/**
 * Quack is the templating engine which powers Duck Fusion projects
 * 
 * Notes: 
 * 
 * Each action's template should have a [[use:x]] at the top, telling it which actual full template file to use, with the header, footer, etc... then the content of the action tpl
 * should be inside a [[section]] which can be [[import]]ed in the template file.
 * 
 * 13/03/2015 - Issues with delim regexs. If we have the /U flag, then we can have multiple on same line, e.g. {echo 'sup again'} {echo 'sup now'}, but then it fucks up with double and triple delims
 *            - If we don't have the /U flag, then the other delims work, but we get a parse error if we have multiple on same line, as it's wrapping to first and last
 *            - Will leave with /U flag for now, but needs fixing
 * 
 * 30/06/2017 - Future possibility - Look at redoing this, so it doesn't all parse one big string, breaks it down and goes through one "token" at a time, like in the twig template engine
 *
 * @author Conn Warwicker
 */

// Issues
// - [[noparse]] doesn't work properly, if you are inside a tag and you are parsing the end of that tag, e.g. [[section:a]][[noparse]][[section:b]]hi[[endsection]][[endnoparse]][[endsection]]
// switch case doesn't work unless first case is at the beginning of line. Any spaces or tabs cause an error. Need to strip any whitespace between them.


class Quack implements \DF\Helpers\Parser {
    
    private $cache = false;
    private $cache_life = 0;
    
    private $request_string = false;
    private $cache_dir = false;
    private $tmp_dir = false;
    
    private $sections = array();
    private $noParses = array();
    private $vars = array();
    
    private $inSwitch = false;
    
    private static $controls = array(
        'use',
        'import',
        'noparse',
        'section'
    );
    
    const DELIM = "|";
    const DELIM_OPTION = ":";
    
    const CACHE_LIFETIME = 3600; // 1 hour. By default this will be used if caching is enabled and a lifetime hasn't been set
    
    const CACHE_DYNAMIC = 1; // Cache the PHP to be executed - So that we don't have to parse the views again we can just include it straight away, but it will still be dynamic and show any changes each time, as it is still just calling PHP
    const CACHE_STATIC = 2; // Cache the actual static output of the script

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
     * Construct the Quack engine object
     */
    public function __construct() {
        
        // Cache directory in application
        $this->cache_dir = df_APP_ROOT . df_DS . 'tmp' . df_DS . 'cache' . df_DS;
        $this->tmp_dir = df_APP_ROOT . df_DS . 'tmp' . df_DS;
        
    }
    
    /**
     * Set a variable's value
     * @param type $var
     * @param type $val
     * @return $this
     */
    public function set($var, $val){
        
        $this->vars[$var] = $val;
        return $this;
        
    }
    
    /**
     * Set an array of variables
     * @param type $vars
     */
    public function setVars($vars){
        $this->vars = $vars;
    }
    
    /**
     * Set the cache directory to use
     * @param type $dir
     * @return $this
     */
    public function setCacheDir($dir){
        $this->cache_dir = $dir;
        return $this;
    }
    
    /**
     * Set the request string
     * @param type $str
     * @return $this
     */
    public function setRequestString($str){
        $this->request_string = $str;
        return $this;
    }
    
    /**
     * Set the caching type, to be either dynamic or static
     * @param type $val
     * @return $this
     */
    public function setCaching($val){
        
        if ($val == self::CACHE_DYNAMIC || $val == self::CACHE_STATIC){
            $this->cache = $val;
        }
        
        return $this;
        
    }
    
    /**
     * Set the cache lifetime
     * @param type $val
     * @return $this
     */
    public function setCachingLife($val){
        $this->cache_life = $val;
        return $this;
    }
    
    /**
     * Get when the cache will expire
     * @return type
     */
    public function getCacheExpireTime(){
        
        if ($this->cache_life > 0){
            return time() + $this->cache_life;
        }
        
        return time() + self::CACHE_LIFETIME;
        
    }
    
    
    /**
     * Render a template
     * @param string $view This can be either a full path to a file, a relative path to a file from the app directory or otherwise just the name of a view in the views directory
     */
    public function render($view){
                        
        $now = time();
                                
        // Get current URL string
        if ($this->cache && $this->cache_dir && $this->request_string){
            
            $hash = hash('md5', $this->request_string);
            $cacheFile = $hash . '.cached';
            $jsonFile = $hash . '.info';
            
            // Dynamic caching - This means we have cached the PHP script after it was parsed, and still want to execute it
            if ($this->cache === self::CACHE_DYNAMIC)
            {
             
                // Try and find the cached file
                if (file_exists( $this->cache_dir . $cacheFile ))
                {
                    
                    // Is there an info file for it, defining it's properties?
                    // If not, then something is wrong, so do NOT load the cached file and let it execute as normal and create a new one
                    if (file_exists( $this->cache_dir . $jsonFile ))
                    {
                        
                        $info = json_decode(file_get_contents( $this->cache_dir . $jsonFile ));
                        
                        // Make sure it hasn't expired
                        // If it has, do NOT load the cached file, let it go on and create a new one
                        if (isset($info->expires) && $info->expires > $now)
                        {
                            $this->serve( $this->cache_dir . $cacheFile );
                            return true;
                        }
                        
                    }
                    
                }
                
            }
            
            // Static caching - This means we have cached the actual HTML output, so we just want to display it and do nothing else
            elseif ( $this->cache === self::CACHE_STATIC )
            {
                
                // Try and find the cached file
                if (file_exists( $this->cache_dir . $cacheFile ))
                {
                    
                    // Is there an info file for it, defining it's properties?
                    // If not, then something is wrong, so do NOT load the cached file and let it execute as normal and create a new one
                    if (file_exists( $this->cache_dir . $jsonFile ))
                    {
                        
                        $info = json_decode(file_get_contents( $this->cache_dir . $jsonFile ));
                        
                        // Make sure it hasn't expired
                        // If it has, do NOT load the cached file, let it go on and create a new one
                        if (isset($info->expires) && $info->expires > $now)
                        {
                            $contents = file_get_contents( $this->cache_dir . $cacheFile );
                            echo $contents;
                            return true;
                        }
                        
                    }
                    
                }
                
            }
            
            
            
        } 
        
        // First check if this is a full path to a file
        if (file_exists($view)){
            // It is, so we will use that
            $file = file_get_contents($view);
        } 
        
        // Next check if it is a relative path from the app directory (adding .html on as it will be left out otherwise)
        elseif ( file_exists(df_APP_ROOT . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ) ) ){
            $file = file_get_contents(df_APP_ROOT . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ));
        }
        
        // Next check if it's a path to a file in the app's views directory
        elseif ( file_exists(df_APP_ROOT . df_DS . 'views' . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ) ) ){
            $file = file_get_contents(df_APP_ROOT . df_DS . 'views' . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ));
        }
        
        // Otherwise we failed
        else {
            return false;
        }
                        
        $content = $this->parse($file);
        $result = $this->save($content);
                
        if ($result)
        {
            
            // Now let's actually serve the file to the browser
            
            // Is it a cached file?
            if (isset($result['cached'])){
                $this->serve($result['cached']);
            }
            
            // No, it's just a tmp file
            elseif (isset($result['file']))
            {
                
                $this->serve($result['file']);
                
                // Delete it as it's just a tmp file we won't use again, since we haven't said to cache any of this
                unlink($result['file']);
                
            }
            
        }
        
       return true;
        
    }
    
    /**
     * Serve a given file to the browser
     */
    protected function serve($file){
                
        ob_start();
                
        extract($this->vars);
        
        include_once $file;
        
        ob_end_flush();
                
    }
    
    /**
     * Save contents to a file location
     * @param type $content
     * @param type $location
     */
    protected function saveFile($content, $location){
        
        // The PHP file to use
        $handle = fopen($location, 'w');
        if ($handle)
        {
            fwrite($handle, $content);
            fclose($handle);
        }
        
    }
    
    /**
     * Save the rendered content either to a cache, if caching is enabled, or to a tmp file for including
     * @param type $content
     * @return string
     */
    protected function save($content){
        
        $return = array();
        
        $hash = hash('md5', $this->request_string);
        
        // Is caching enabled?
        if ($this->cache){
        
            // Dynamic - Save the php script
            if ($this->cache === self::CACHE_DYNAMIC){
                
                // The script
                $cacheFile = $hash . '.cached';
                $this->saveFile($content, $this->cache_dir . $cacheFile);
                
                // The info
                $jsonFile = $hash . '.info';
                $obj = new \stdClass();
                $obj->request = $this->request_string;
                $obj->type = 'dynamic';
                $obj->time = time();
                $obj->expires = $this->getCacheExpireTime();
                $this->saveFile(json_encode($obj), $this->cache_dir . $jsonFile);
                
            } 
            // Static - Save the final output
            elseif ($this->cache === self::CACHE_STATIC){
                
                // Save as a tmp file as we would if no caching
                $handle = fopen($this->tmp_dir . $hash, 'w');
                if ($handle)
                {
                    fwrite($handle, $content);
                    fclose($handle);
                }
                 
                // Now let's get that content and include it so that it executes as PHP
                $newContent = $this->getScriptOutput( $this->tmp_dir . $hash );
                
                // Delete that tmp file
                unlink( $this->tmp_dir . $hash );
                
                // Now save that as the cached file
                $cacheFile = $hash . '.cached';
                $this->saveFile($newContent, $this->cache_dir . $cacheFile);
                
                // The info
                $jsonFile = $hash . '.info';
                $obj = new \stdClass();
                $obj->request = $this->request_string;
                $obj->type = 'static';
                $obj->time = time();
                $obj->expires = $this->getCacheExpireTime();
                $this->saveFile(json_encode($obj), $this->cache_dir . $jsonFile);
                
            }

            $return['cached'] = $this->cache_dir . $cacheFile;

        } else {
            
            // Otherwise, just create a tmp file, return it and then delete it
            $handle = fopen($this->tmp_dir . $hash, 'w');
            if ($handle)
            {
                fwrite($handle, $content);
                fclose($handle);
            }
            
            $return['file'] = $this->tmp_dir . $hash;
            
        }
        
        return $return;
        
    }
    
    /**
     * Get the contents of an included script
     * @param type $____script
     * @return type
     */
    protected function getScriptOutput($____script){
        
        ob_start();
        extract($this->vars);
        include_once $____script;
        $____newContent = ob_get_contents();
        ob_end_clean();
        
        return $____newContent;
        
    }
    
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