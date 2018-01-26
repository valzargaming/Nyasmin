<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a received embed from a message. This class can also be used to make a Rich Embed.
 *
 * @property string            $type               The embed type.
 * @property string|null       $title              The title, or null.
 * @property array|null        $author             The author (array of name, icon, url), or null.
 * @property string|null       $description        The description, or null.
 * @property string|null       $url                The URL, or null.
 * @property int|string|null   $timestamp          The timestamp, or the set timestamp (as ISO string), or null.
 * @property int|null          $color              The color, or null.
 * @property array|null        $footer             The author (array of name, icon), or null.
 * @property array|null        $image              The image (array of url, height, width), or null.
 * @property array|null        $thumbnail          The thumbnail (array of url, height, width), or null.
 * @property array|null        $video              The video (array of url, height, width), or null.
 * @property array|null        $provider           The provider (array of name, url), or null.
 *
 * @property \DateTime|null    $datetime           The DateTime instance of timestamp, or null.
 */
class MessageEmbed extends Base {
    protected $type = 'rich';
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
     * Constructs a new instance.
     * @param array  $embed
     */
    function __construct(array $embed = array()) {
        if(!empty($embed)) {
            $this->type = $embed['type'] ?? 'rich';
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
            $this->timestamp = (!empty($embed['timestamp']) ? (new \DateTime($embed['timestamp']))->getTimestamp() : null);
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
     * @inheritDoc
     *
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'datetime':
                return (new \DateTime('@'.$this->timestamp));
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Set the author of this embed.
     * @param string  $name      Maximum length is 256 characters.
     * @param string  $iconurl   The URL to the icon.
     * @param string  $url       The URL to the author.
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setAuthor($name, string $iconurl = '', string $url = '') {
        $name = (string) $name;
        
        if(\mb_strlen($name) > 256) {
            throw new \InvalidArgumentException('Author name can not be longer than 256 characters.');
        }
        
        $this->author = array(
            'name' => $name,
            'icon_url' => $iconurl,
            'url' => $url
        );
        
        return $this;
    }
    
    /**
     * Set the color of this embed.
     * @param mixed  $color
     * @return $this
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor()
     */
    function setColor($color) {
        $this->color = \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor($color);
        return $this;
    }
    
    /**
     * Set the description of this embed.
     * @param string  $description  Maxiumum length is 2048 characters.
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setDescription($description) {
        $description = (string) $description;
        
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
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setFooter($text, string $iconurl = '') {
        $text = (string) $text;
        
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
     * @return $this
     */
    function setImage($url) {
        $this->image = array('url' => (string) $url);
        return $this;
    }
    
    /**
     * Set the thumbnail of this embed.
     * @param string  $url
     * @return $this
     */
    function setThumbnail($url) {
        $this->thumbnail = array('url' => (string) $url);
        return $this;
    }
    
    /**
     * Set the timestamp of this embed.
     * @param int|null  $timestamp
     * @return $this
     */
    function setTimestamp(?int $timestamp = null) {
        $this->timestamp = (new \DateTime(($timestamp !== null ? '@'.$timestamp : 'now')))->format('c');
        return $this;
    }
    
    /**
     * Set the title of this embed.
     * @param string  $title    Maximum length is 256 characters.
     * @return $this
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
     * @return $this
     */
    function setURL(string $url) {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Adds a field to this embed.
     * @param string  $title    Maximum length is 256 characters.
     * @param string  $value    Maximum length is 1024 characters.
     * @param bool    $inline   Whether this field gets shown with other inline fields on one line.
     * @return $this
     * @throws \RangeException|\InvalidArgumentException
     */
    function addField($title, $value, bool $inline = false) {
        if(\count($this->fields) >= 25) {
            throw new \RangeException('Embeds can not have more than 25 fields');
        }
        
        $title = (string) $title;
        $value = (string) $value;
        
        if(\mb_strlen($title) > 256) {
            throw new \InvalidArgumentException('Embed title can not be longer than 256 characters');
        }
        
        if(\mb_strlen($value) > 1024) {
            throw new \InvalidArgumentException('Embed value can not be longer than 1024 characters');
        }
        
        $this->fields[] = array(
            'name' => $title,
            'value' => $value,
            'inline' => $inline
        );
        
        return $this;
    }
}
