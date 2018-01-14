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
 * Represents a partial channel.
 *
 * @property string       $id                The channel ID.
 * @property string       $name              The channel name.
 * @property int          $createdTimestamp  The timestamp when this channel was created.
 * @property string       $type              The type of the channel.
 *
 * @property \DateTime   $createdAt          The DateTime instance of createdTimestamp.
 */
class PartialChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface {
    
    protected $id;
    protected $name;
    protected $icon;
    protected $type;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client);
        
        $this->id = $channel['id'];
        $this->name = $channel['name'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES[$channel['type']];
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
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
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
        }
        
        return parent::__get($name);
    }
}
