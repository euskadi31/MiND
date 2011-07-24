<?php
/**
 * Swift
 *
 * @category   Swift
 * @package    Swift_Xml
 * @copyright  Copyright (c) 2009 Axel ETCHEVERRY. (http://www.axel-etcheverry.com)
 * @license    http://creativecommons.org/licenses/by/3.0/     Creative Commons 3.0
 */
namespace Swift;

class Xml extends \SimpleXMLElement
{
    public function getAttribute($key)
    {
        $attributes = $this->getAttributes();

        if(isset($attributes[$key]) && !empty($attributes[$key])) {
            return $attributes[$key];
        } else {
            return;
        }
    }
    
    public function hasAttribute($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]);
    }

    public function getAttributes()
    {
        $attributes = array();
        foreach($this->attributes() as $key => $val) {
            $attributes[(string)$key] = (string)$val;
        }
        return $attributes;
    }
}