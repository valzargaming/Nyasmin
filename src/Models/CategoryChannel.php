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
 * Represents a guild's category channel.
 * @property string                                    $id                     The ID of the channel.
 * @property string                                    $name                   The channel name.
 * @property string                                    $type                   The channel type ({@see \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES}).
 * @property int                                       $createdTimestamp       The timestamp of when this channel was created.
 * @property int                                       $position               The channel position.
 * @property \CharlotteDunois\Yasmin\Utils\Collection  $permissionOverwrites   A collection of PermissionOverwrite instances.
 *
 * @property \CharlotteDunois\Yasmin\Utils\Collection  $children               Returns all channels which are childrens of this category.
 * @property \DateTime                                 $createdAt              The DateTime instance of createdTimestamp.
 */
class CategoryChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface {
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    protected $guild;
    
    protected $id;
    protected $type;
    protected $name;
    protected $position;
    protected $permissionOverwrites;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES[$channel['type']];
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        
        $this->_patch($channel);
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
            case 'children':
                return $this->guild->channels->filter(function ($channel) {
                    return $channel->parentID === $this->id;
                });
            break;
                case 'createdAt':
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
                break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * @internal
     */
    function _patch(array $channel) {
        $this->permissionOverwrites = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->position = $channel['position'] ?? $this->position ?? 0;
        
        if(!empty($channel['permissions_overwrites'])) {
            foreach($channel['permissions_overwrites'] as $permission) {
                $this->permissionOverwrites->set($permission['id'], new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($this->client, $this, $permission));
            }
        }
    }
}
