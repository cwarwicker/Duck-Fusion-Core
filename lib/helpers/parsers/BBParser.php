<?php
namespace DF\Helpers\parsers;

/**
 * Description of BBParser
 *
 * @author Conn
 */
class BBParser implements \DF\Helpers\Parser {
   
    const BB_OPT_HTML = 'html';
    const BB_OPT_NL2BR = 'nl2br';
    const BB_OPT_AUTOLINK = 'link';
    
    const REGEX_BOLD = "/\[b\](.+?)\[\/b\]/i";
    const REGEX_ITALIC = "/\[i\](.+?)\[\/i\]/i";
    const REGEX_UNDERLINED = "/\[u\](.+?)\[\/u\]/i";
    const REGEX_STRIKE = "/\[s\](.+?)\[\/s\]/i";
    const REGEX_IMG = "/\[img( size=[0-9]+)*\](.+?)\[\/img\]/";
    const REGEX_URL = "/\[url( title=(.+?))*\](.+?)\[\/url\]/";
    const REGEX_YOUTUBE = "/\[youtube\](.+?)\[\/youtube\]/";
    const REGEX_COLOUR = "/\[colo[u]*r=(.+?)\](.+?)\[\/colo[u]*r\]/";
    const REGEX_LIST = "/\[list( ordered)?\](.+?)\[\/list\]/s";
    const REGEX_LIST_ITEM = "/\[item\](.+?)\[\/item\]/";
    
    // >>DFTODO
    // videos (other than youtube), emotes, quotes, alignment, code
    
    /**
     * Parse bbcode to html for output
     * @param type $content The string of bbcode to parse
     * @param array $options [optional] Should new lines be converted to linebreaks in the html? 'nl2br'
     *                                  Should any html content passed in be converted to specialchars? 'html'
     *                                  If options parameter left as false, all will be assumed yes
     * @return string The parsed string
     */
    public function parse($content, $options = false)
    {
                        
        // HTML?
        if (!$options || isset($options[self::BB_OPT_HTML]))
        {
            $content = \df_html($content);
        }
        
        // Parse for bold
        $this->parseBold($content);
        
        // Parse for italic
        $this->parseItalic($content);
        
        // Parse for underlined
        $this->parseUnderlined($content);
        
        // Parse for strike-through
        $this->parseStrikeThrough($content);
        
        // Parse for colours
        $this->parseColour($content);
        
        // Parse for lists
        $this->parseLists($content);
        
        // Parse for images
        $this->parseImage($content);
        
        // Parse for urls
        $this->parseURL($content);
        
        // Parse for Youtube videos
        $this->parseYouTube($content);
        
        
        
        
        // Newlines?
        if (!$options || isset($options[self::BB_OPT_NL2BR]))
        {
            $content = nl2br($content);
        }
        
        return $content;
        
    }
    
    /**
     * Convert [b][/b] to bold
     * @param type $content
     */
    protected function parseBold(&$content){
        
        $content = preg_replace(self::REGEX_BOLD, '<b>$1</b>', $content);
        
    }
    
    /**
     * Convert [i][/i] to italic
     * @param type $content
     */
    protected function parseItalic(&$content){
        
        $content = preg_replace(self::REGEX_ITALIC, '<i>$1</i>', $content);
        
    }
    
    /**
     * Convert [u][/u] to underlined
     * @param type $content
     */
    protected function parseUnderlined(&$content){
        
        $content = preg_replace(self::REGEX_UNDERLINED, '<u>$1</u>', $content);
        
    }
    
    
    /**
     * Convert [u][/u] to underlined
     * @param type $content
     */
    protected function parseStrikeThrough(&$content){
        
        $content = preg_replace(self::REGEX_STRIKE, '<s>$1</s>', $content);
        
    }
    
    /**
     * Convert [img][/img] to image
     * @param type $content
     */
    protected function parseImage(&$content){
                
        $content = preg_replace_callback(self::REGEX_IMG, function($matches){
                        
            $url = \df_html($matches[2]);
            
            // Has size attribute
            if (!empty($matches[1]))
            {
                preg_match("/([0-9]+)/", $matches[1], $size);
                $size = trim( $size[0] );
                return "<img src='{$url}' alt='{$url}' style='max-width:{$size}px;max-height:{$size}px;' />";
                
            }
            else
            {
                return "<img src='{$url}' alt='{$url}' />";
            }
            
        }, $content);
        
    }
    
    /**
     * Convert [url][/url] to hyperlink
     * @param type $content
     */
    protected function parseURL(&$content){
        
        $content = preg_replace_callback(self::REGEX_URL, function($matches){
                        
            $url = \df_html($matches[3]);
            
            // Has size attribute
            if (!empty($matches[2]))
            {
                $title = trim( $matches[2] );
                return "<a href='{$url}' target='_blank'>{$title}</a>";
                
            }
            else
            {
                return "<a href='{$url}' target='_blank'>{$url}</a>";
            }
            
        }, $content);
        
    }
    
    /**
     * Convert [youtube][/youtube] to Youtube video
     * @param type $content
     */
    protected function parseYoutube(&$content){
        
        $content = preg_replace(self::REGEX_YOUTUBE, '<iframe class="df-youtube" src="//www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>', $content);
        
    }
    
    
    /**
     * Convert [colour=blue][/colour] to Text colour
     * @param type $content
     */
    protected function parseColour(&$content){
        
        $content = preg_replace(self::REGEX_COLOUR, '<span style="color:$1">$2</span>', $content);
        
    }
    
    
    /**
     * Convert [list][item]1[/item][/list] into a list
     * @param type $content
     */
    protected function parseLists(&$content){
        
        // List
        $content = preg_replace_callback(self::REGEX_LIST, function($matches){
                        
            $ordered = $matches[1];
            $items = \df_html($matches[2]);
            $items = str_replace(array("\r", "\n"), '', $items);
            
            // Has size attribute
            if (!empty($ordered))
            {
                return "<ol>{$items}</ol>";
                
            }
            else
            {
                return "<ul>{$items}</ul>";
            }
            
        }, $content);
        
        
        // Items
        $content = preg_replace(self::REGEX_LIST_ITEM, '<li>$1</li>', $content);
        
    }
    
    
}
