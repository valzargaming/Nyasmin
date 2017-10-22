<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Represents a received embed from a message.
 */
class MessageEmbed extends Structure { //TODO: Implementation
    protected $title;
    protected $type;
    protected $description;
    protected $url;
    protected $timestamp;
    protected $color;
    protected $footer;
    protected $image;
    protected $thumbnail;
    protected $video;
    protected $provider;
    protected $author;
    protected $fields = array();
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $embed = array()) { //TODO: Implementation
        parent::__construct($client);
        
        if(!empty($embed)) {
            $this->title = $embed['title'];
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
