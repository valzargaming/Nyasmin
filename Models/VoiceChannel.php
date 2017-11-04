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
 * Represents a guild's voice channel.
 * @todo Implementation
 */
class VoiceChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\VoiceChannelInterface {
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    protected $guild;
    
    protected $id;
    protected $type;
    protected $createdTimestamp;
    
    protected $name;
    protected $bitrate;
    protected $members;
    protected $parentID;
    protected $position;
    protected $permissionOverwrites;
    protected $userLimit;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->members = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->permissionOverwrites = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES[$channel['type']];
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->bitrate = $channel['bitrate'] ?? $this->bitrate ?? 0;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        $this->userLimit = $channel['user_limit'] ?? $this->userLimit ?? 0;
        
        if(!empty($channel['permission_overwrites'])) {
            foreach($channel['permission_overwrites'] as $permission) {
                $overwrite = new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($client, $this, $permission);
                $this->permissionOverwrites->set($overwrite->id, $overwrite);
            }
        }
    }
    
    /**
     * @inheritdoc
     * @property-read  \CharlotteDunois\Yasmin\Models\ChannelCategory|null  $parent  Returns the channel's parent, or null.
     * @property-read  bool|null                                            $permissionsLocked      If the permissionOverwrites match the parent channel, null if no parent.
     *
     * @throws \Exception
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
                    if($parent->permissionOverwrites->count() !== $this->permissionOverwrites->count()) {
                        return false;
                    }
                    
                    return !((bool) $this->permissionOverwrites->first(function ($perm) use ($parent) {
                        $permp = $parent->permissionOverwrites->get($perm->id);
                        return (!$permp || $perm->allowed->bitfield !== $permp->allowed->bitfield || $perm->denied->bitfield !== $permp->denied->bitfield);
                    }));
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
}
