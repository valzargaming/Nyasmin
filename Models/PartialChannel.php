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
 * Represents a partial channel.
 */
class PartialChannel extends Structure
    implements \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface { //TODO: Implementation
    
    protected $id;
    protected $name;
    protected $icon;
    protected $splash;
    
    protected $createdTimestamp;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client);
        
        $this->id = $channel['id'];
        $this->name = $guild['name'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPE[$channel['type']];
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @property-read string       $id                The channel ID.
     * @property-read string       $name              The channel name.
     * @property-read int          $createdTimestamp  The timestmap when this channel was created.
     * @property-read string       $type              The type of the channel.
     *
     * @property-read \DateTime   $createdAt          The DateTime object of createdTimestamp.
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
