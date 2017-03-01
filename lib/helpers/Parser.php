<?php
namespace DF\Helpers;

/**
 * Template will call preg_replace_callback($pattern, 'my_parse_method', $string); on anything it renders, if used
 */
interface Parser
{
    
    public function parse($content);
    
}