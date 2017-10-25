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
 * Represents a guild's voice channel.
 */
class VoiceChannel extends TextBasedChannel
    implements \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface { //TODO: Implementation
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    protected $guild;
    
    protected $name;
    protected $bitrate;
    protected $members;
    protected $parentID;
    protected $position;
    protected $permissionsOverwrites;
    protected $userLimit;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Structures\Guild $guild, array $channel) {
        parent::__construct($client, $channel);
        $this->guild = $guild;
        
        $this->members = new \CharlotteDunois\Yasmin\Structures\Collection();
        $this->permissionsOverwrites = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->bitrate = $channel['bitrate'] ?? $this->bitrate ?? 0;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        $this->userLimit = $channel['user_limit'] ?? $this->userLimit ?? 0;
        
        if(!empty($channel['permissions_overwrites'])) {
            foreach($channel['permissions_overwrites'] as $permission) {
                $this->permissionsOverwrites->set($permission['id'], new \CharlotteDunois\Yasmin\Structures\PermissionOverwrite($client, $this, $permission));
            }
        }
    }
    
    /**
     * @inheritdoc
     * @property-read  \CharlotteDunois\Yasmin\Structures\ChannelCategory|null  $parent  Returns the channel's parent, or null.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'parent':
                return $this->guild->channels->get($this->parentID);
            break;
            case 'permissionsLocked':
                $parent = $this->__get('parent');
                if($parent) {
                    if($parent->permissionsOverwrites->count() !== $this->permissionsOverwrites->count()) {
                        return false;
                    }
                    
                    return !((bool) $this->permissionsOverwrites->first(function ($perm) use ($parent) {
                        $permp = $parent->permissionsOverwrites->get($perm->id);
                        return (!$permp || $perm->allowed->bitfield !== $permp->allowed->bitfield || $perm->denied->bitfield !== $permp->denied->bitfield);
                    }));
                }
            break;
        }
        
        return null;
    }
}
