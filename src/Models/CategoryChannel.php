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
 * Represents a guild's category channel.
 *
 * @property string                                               $id                     The ID of the channel.
 * @property string                                               $name                   The channel name.
 * @property string                                               $type                   The channel type ({@see \CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES}).
 * @property \CharlotteDunois\Yasmin\Models\Guild                 $guild                  The guild this category channel belongs to.
 * @property int                                                  $createdTimestamp       The timestamp of when this channel was created.
 * @property int                                                  $position               The channel position.
 * @property \CharlotteDunois\Yasmin\Utils\Collection             $permissionOverwrites   A collection of PermissionOverwrite instances.
 *
 * @property \CharlotteDunois\Yasmin\Interfaces\StorageInterface  $children               DEPRECATED: Returns all channels which are childrens of this category.
 * @property \DateTime                                            $createdAt              The DateTime instance of createdTimestamp.
 */
class CategoryChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface {
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    /**
     * The guild this category channel belongs to.
     * @var \CharlotteDunois\Yasmin\Models\Guild
     */
    protected $guild;
    
    /**
     * The ID of the channel.
     * @var string
     */
    protected $id;
    
    /**
     * The channel type.
     * @var string
     */
    protected $type;
    
    /**
     * The channel name.
     * @var string
     */
    protected $name;
    
    /**
     * The channel position.
     * @var int
     */
    protected $position;
    
    /**
     * A collection of PermissionOverwrite instances.
     * @var \CharlotteDunois\Yasmin\Utils\Collection
     */
    protected $permissionOverwrites;
    
    /**
     * The timestamp of when this channel was created.
     * @var int
     */
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->id = (string) $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES[$channel['type']];
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        $this->permissionOverwrites = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->_patch($channel);
    }
    
    /**
     * {@inheritdoc}
     * @return mixed
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'children': // TODO: DEPRECATED
                return $this->getChildren();
            break;
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Returns all channels which are childrens of this category.
     * @return \CharlotteDunois\Yasmin\Interfaces\StorageInterface
     */
    function getChildren() {
        return $this->guild->channels->filter(function ($channel) {
            return ($channel->parentID === $this->id);
        });
    }
    
    /**
     * @return void
     * @internal
     */
    function _patch(array $channel) {
        $this->name = (string) ($channel['name'] ?? $this->name ?? '');
        $this->position = (int) ($channel['position'] ?? $this->position ?? 0);
        
        if(isset($channel['permission_overwrites'])) {
            $this->permissionOverwrites->clear();
            
            foreach($channel['permission_overwrites'] as $permission) {
                $this->permissionOverwrites->set($permission['id'], new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($this->client, $this, $permission));
            }
        }
    }
}
