<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class MessageEmbed { //TODO: Implementation
    protected $id;
    
    /**
     * @access private
     */
    function __construct(array $embed = array()) {
        if(!empty($embed)) {
            $this->id = $attachment['id'];
        }
    }
    
    /**
     * @property-read string       $id                 The attachment ID.
     * @property-read string       $filename           The filename.
     * @property-read int          $size               The filename in bytes.
     * @property-read string       $url                The url to the file.
     * @property-read int|null     $height             The height (if image).
     * @property-read int|null     $width              The width (if image).
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return null;
    }
}
