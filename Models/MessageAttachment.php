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
class MessageAttachment extends Part {
    protected $id;
    protected $filename;
    protected $size;
    protected $url;
    protected $height;
    protected $width;
    
    protected $createdTimestamp;
    
    /**
     * @access private
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
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return (new \DateTime('@'.$this->createdTimestamp));
            break;
        }
        
        return null;
    }
}
