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
 * Represents a partial channel (of a guild or a group DM).
 *
 * @property string       $id                The channel ID.
 * @property string|null  $name              The channel name.
 * @property string       $type              The type of the channel.
 * @property string|null  $icon              The icon of the channel.
 * @property int          $createdTimestamp  The timestamp when this channel was created.
 *
 * @property \DateTime    $createdAt         The DateTime instance of createdTimestamp.
 */
class PartialChannel extends ClientBase {
    protected $id;
    protected $name;
    protected $type;
    protected $icon;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client);
        
        $this->id = $channel['id'];
        $this->name = $channel['name'] ?? null;
        $this->type = \CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES[$channel['type']];
        $this->icon = $channel['icon'] ?? null;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @inheritDoc
     *
     * @throws \RuntimeException
     * @internal
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
     * Returns the group DM's icon URL, or null.
     * @param string    $format  One of png, jpg or webp.
     * @param int|null  $size    One of 128, 256, 512, 1024 or 2048.
     * @return string|null
     */
    function getIconURL(string $format = 'png', ?int $size = null) {
        if($size & ($size - 1)) {
            throw new \InvalidArgumentException('Invalid size "'.$size.'", expected any powers of 2');
        }
        
        if($this->icon !== null) {
            return \CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['url'].\CharlotteDunois\Yasmin\HTTP\APIEndpoints::format(\CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['channelicons'], $this->id, $this->icon, $format).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * Automatically converts to the channel name.
     */
    function __toString() {
        return $this->name;
    }
}
