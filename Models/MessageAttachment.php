<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents an attachment (from a message).
 */
class MessageAttachment extends Base {
    protected $id;
    protected $filename;
    protected $size;
    protected $url;
    protected $height;
    protected $width;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    protected $attachment;
    
    /**
     * @internal
     */
    function __construct(array $attachment = array()) {
        if(!empty($attachment)) {
            $this->id = $attachment['id'];
            $this->filename = $attachment['filename'];
            $this->size = $attachment['size'];
            $this->url = $attachment['url'];
            $this->height = (!empty($attachment['height']) ? $attachment['height'] : null);
            $this->width = (!empty($attachment['width']) ? $attachment['width'] : null);
            
            $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        }
    }
    
    /**
     * @property-read string                                           $id                 The attachment ID.
     * @property-read string                                           $filename           The filename.
     * @property-read int                                              $size               The filename in bytes.
     * @property-read string                                           $url                The url to the file.
     * @property-read int|null                                         $height             The height (if image).
     * @property-read int|null                                         $width              The width (if image).
     * @property-read int                                              $createdTimestamp   The timestamp of when this attachment was created.
     *
     * @property-read \DateTime                                        $createdAt          An DateTime object of the createdTimestamp.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Sets the attachment. Requires allow_url_fopen to be enabled in the php.ini for URLs.
     * @param string  $attachment  An URL or the filepath or the data.
     * @param string  $filename    The filename.
     * @return $this
     */
    function setAttachment($attachment, string $filename = '') {
        $this->attachment = $attachment;
        $this->filename = $filename;
        return $this;
    }
    
    /**
     * @internal
     */
    function jsonSerialize() {
        $props = array(
            'filename' => $this->filename
        );
        
        $file = @\realpath($this->attachment);
        if($file) {
            $props['path'] = $file;
        } elseif(\filter_var($this->attachment, FILTER_VALIDATE_URL)) {
            $props['path'] = $this->attachment;
        } else {
            $props['data'] = $this->attachment;
        }
        
        if(empty($props['filename'])) {
            if(!empty($props['path'])) {
                $props['filename'] = \basename($props['path']);
            } else {
                $props['filename'] = 'file-'.\bin2hex(\random_bytes(3)).'.jpg';
            }
        }
        
        return $props;
    }
}
