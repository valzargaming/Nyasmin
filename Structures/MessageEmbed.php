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
 * Represents a received embed from a message. This class can also be used to make a Rich Embed.
 */
class MessageEmbed extends Structure {
    protected $type;
    protected $title;
    protected $author;
    protected $description;
    protected $url;
    protected $timestamp;
    protected $color;
    protected $footer;
    protected $image;
    protected $thumbnail;
    protected $video;
    protected $provider;
    protected $fields = array();
    
    /**
     * Constructs a new instance. The parameters are only for received embeds. If you use this class to make a Rich Embed, do not pass any parameters, or only do that, if you know what you are doing.
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client = null, array $embed = array()) { //TODO: Implementation
        parent::__construct($client);
        
        if(!empty($embed)) {
            $this->type = $embed['type'];
            $this->title = $embed['title'] ?? null;
            
            if(!empty($embed['author'])) {
                $this->author = array(
                    'name' => $embed['author']['name'] ?? '',
                    'icon' => $embed['author']['icon_url'] ?? '',
                    'url' => $embed['author']['url'] ?? ''
                );
            }
            
            $this->description = $embed['description'] ?? null;
            $this->url = $embed['url'] ?? null;
            $this->timestamp = (!empty($this->timestamp) ? (new \DateTime($this->timestamp))->format('U') : null);
            $this->color = $embed['color'] ?? null;
            
            if(!empty($embed['footer'])) {
                $this->footer = array(
                    'text' => $embed['footer']['text'] ?? '',
                    'icon' => $embed['footer']['icon_url'] ?? ''
                );
            }
            
            if(!empty($embed['image'])) {
                $this->image = array(
                    'url' => $embed['image']['url'],
                    'height' => $embed['image']['height'],
                    'width' => $embed['image']['width']
                );
            }
            
            if(!empty($embed['thumbnail'])) {
                $this->thumbnail = array(
                    'url' => $embed['thumbnail']['url'],
                    'height' => $embed['thumbnail']['height'],
                    'width' => $embed['thumbnail']['width']
                );
            }
            
            if(!empty($embed['video'])) {
                $this->video = array(
                    'url' => $embed['video']['url'],
                    'height' => $embed['video']['height'],
                    'width' => $embed['video']['width']
                );
            }
            
            if(!empty($embed['provider'])) {
                $this->provider = array(
                    'name' => $embed['provider']['name'],
                    'url' => $embed['provider']['url']
                );
            }
            
            $this->fields = $embed['fields'] ?? array();
        }
    }
    
    /**
     * @property-read string            $type               The embed type.
     * @property-read string|null       $title              The title.
     * @property-read array|null        $author             The author (array of name, icon, url), or null.
     * @property-read string|null       $description        The description, or null.
     * @property-read string|null       $url                The URL, or null.
     * @property-read int|null          $timestamp          The timestamp, or null.
     * @property-read int|null          $color              The color, or null.
     * @property-read array|null        $footer             The author (array of name, icon), or null.
     * @property-read array|null        $image              The image (array of url, height, width), or null.
     * @property-read array|null        $thumbnail          The thumbnail (array of url, height, width), or null.
     * @property-read array|null        $video              The video (array of url, height, width), or null.
     * @property-read array|null        $provider           The provider (array of name, url), or null.
     *
     * @property-read \DateTime         $datetime           The DateTime object of timestamp.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'datetime':
                return (new \DateTime('@'.$timestamp));
            break;
        }
        
        return null;
    }
    
    /**
     * Set the author of this embed.
     * @param string  $name      Maximum length is 256 characters.
     * @param string  $iconurl   The URL to the icon.
     * @param string  $url       The URL to the author.
     * @return this
     * @throws \InvalidArgumentException
     */
    function setAuthor(string $name, string $iconurl = '', string $url = '') {
        if(\mb_strlen($name) > 256) {
            throw new \InvalidArgumentException('Author name can not be longer than 256 characters.');
        }
        
        $this->author = array(
            'author' => $name,
            'icon_url' => $iconurl,
            'url' => $icon
        );
        
        return $this;
    }
    
    /**
     * Set the color of this embed.
     * @param mixed   $color
     * @return this
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor
     */
    function setColor($color) {
        $this->color = \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor($color);
        return $this;
    }
    
    /**
     * Set the description of this embed.
     * @param string  $description   Maxiumum length is 2048 characters.
     * @return this
     * @throws \InvalidArgumentException
     */
    function setDescription(string $description) {
        if(\mb_strlen($description) > 2048) {
            throw new \InvalidArgumentException('Embed description can not be longer than 2048 characters');
        }
        
        $this->description = $description;
        return $this;
    }
    
    /**
     * Set the footer of this embed.
     * @param string  $text     Maximum length is 2048 characters.
     * @param string  $iconurl  The URL to the icon.
     * @return this
     * @throws \InvalidArgumentException
     */
    function setFooter(string $text, string $iconurl = '') {
        if(\mb_strlen($text) > 2048) {
            throw new \InvalidArgumentException('Footer text can not be longer than 2048 characters.');
        }
        
        $this->footer = array(
            'text' => $text,
            'icon_url' => $iconurl
        );
        
        return $this;
    }
    
    /**
     * Set the image of this embed.
     * @param string  $url
     * @return this
     */
    function setImage(string $url) {
        $this->image = $url;
        return $this;
    }
    
    /**
     * Set the thumbnail of this embed.
     * @param string  $url
     * @return this
     */
    function setThumbnail(string $url) {
        $this->thumbnail = $url;
        return $this;
    }
    
    /**
     * Set the timestamp of this embed.
     * @param int  $timestamp
     * @return this
     */
    function setTimestamp(int $timestamp = null) {
        $this->timestamp = (new \DateTime(($timestamp !== null ? '@'.$timestamp : 'now')))->format('r');
        return $this;
    }
    
    /**
     * Set the title of this embed.
     * @param string  $title    Maximum length is 256 characters.
     * @return this
     * @throws \InvalidArgumentException
     */
    function setTitle(string $title) {
        if(\mb_strlen($title) > 256) {
            throw new \InvalidArgumentException('Embed title can not be longer than 256 characters');
        }
        
        $this->title = $title;
        return $this;
    }
    
    /**
     * Set the URL of this embed.
     * @param string  $url
     * @return this
     */
    function setURL(string $url) {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Adds a field to this embed.
     * @param string  $title    Maximum length is 256 characters.
     * @param string  $value    Maximum lengt is 1024 characters.
     * @param bool    $inline
     * @return this
     * @throws \RangeException|\InvalidArgumentException
     */
    function addField(string $title, string $value, bool $inline = false) {
        if(\count($this->fields) >= 25) {
            throw new \RangeException('Embeds can not have more than 25 fields');
        }
        
        if(\mb_strlen($title) > 256) {
            throw new \InvalidArgumentException('Embed title can not be longer than 256 characters');
        }
        
        if(\mb_strlen($value) > 1024) {
            throw new \InvalidArgumentException('Embed value can not be longer than 1024 characters');
        }
        
        $this->fields[] = array(
            'title' => $title,
            'value' => $value,
            'inline' => $inline
        );
        
        return $this;
    }
}
