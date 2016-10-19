<?php

/**
 * Description of Exception
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF;

class DFException  extends \Exception{
    
    protected $context;
    protected $expected;
    protected $recommended;

    public function __construct($context, $message, $expected = null, $recommended = null) {
        $this->context = $context;
        $this->expected = $expected;
        $this->recommended = $recommended;
        parent::__construct($message);
    }

    public function getContext(){
        return $this->context;
    }
    
    public function getExpected(){
        return $this->expected;
    }
    
    public function getRecommended(){
        return $this->recommended;
    }
    
    /**
     * Get the full exception message in the format we want
     * @return string
     */
    public function getException(){
        
        $output = "";
        $output .= "<div>";
        $output .= "<h1>" . df_string('exception') . "</h1>";
        $output .= "<h2>[" . $this->getContext() . "]</h2><br>";
        $output .= "<em>".$this->getMessage()."</em><br>";
        
        if (!is_null($this->getExpected())){
            $output .= "<br>";
            $output .=  "<strong>".df_string('expected') . "</strong> - " . $this->getExpected();
        }
        
        if (!is_null($this->getRecommended())){
            $output .= "<br>";
            $output .= "<strong>".df_string('recommended')."</strong> - " . $this->getRecommended();
        }
        
        $output .= "<br><br>";
        
        $debugtrace = debug_backtrace();
        if ($debugtrace)
        {
            foreach($debugtrace as $trace)
            {
                $file = (isset($trace['file'])) ? $trace['file'] : '?';
                $line = (isset($trace['line'])) ? $trace['line'] : '?';
                $output .= "<div class='notifytiny' style='text-align:center !important;'>{$file}:{$line}</div>";
            }
        }
                
        $output .= "</div>";
        return $output;
        
    }
    
    public function __destruct() {
        
    }

}
