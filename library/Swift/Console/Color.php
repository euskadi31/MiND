<?php
/**
 * Swift
 *
 * @category   Swift
 * @package    Swift_Console
 * @copyright  Copyright (c) 2009 Axel ETCHEVERRY. (http://www.axel-etcheverry.com)
 * @license    http://creativecommons.org/licenses/by/3.0/     Creative Commons 3.0
 */
namespace Swift\Console;

class Color
{
    private $_foreground_colors = array(
        'black'         => '0;30',
        'dark_gray'     => '1;30',
        'blue'          => '0;34',
        'light_blue'    => '1;34',
        'green'         => '0;32',
        'light_green'   => '1;32',
        'cyan'          => '0;36',
        'light_cyan'    => '1;36',
        'red'           => '0;31',
        'light_red'     => '1;31',
        'purple'        => '0;35',
        'light_purple'  => '1;35',
        'brown'         => '0;33',
        'yellow'        => '1;33',
        'light_gray'    => '0;37',
        'white'         => '1;37'
    );
    
    private $_background_colors = array(
        'black'         => '40',
        'red'           => '41',
        'green'         => '42',
        'yellow'        => '43',
        'blue'          => '44',
        'magenta'       => '45',
        'cyan'          => '46',
        'light_gray'    => '47'
    );

    public function getColoredString($string, $foregroundColor = null, $backgroundColor = null)
    {
        $coloredString = "";
        
        if(isset($this->_foregroundColors[$foregroundColor])) {
            $coloredString .= "\033[" . $this->_foregroundColors[$foregroundColor] . "m";
        }
        
        if(isset($this->_backgroundColors[$backgroundColor])) {
        	$coloredString .= "\033[" . $this->_backgroundColors[$backgroundColor] . "m";
        }
        
        return $coloredString .= $string . "\033[0m";
    }
    
    public static function colored($string, $foregroundColor = null, $backgroundColor = null) 
    {
        $color = new self();
        return $color->getColoredString($string, $foregroundColor, $backgroundColor);
    }
}