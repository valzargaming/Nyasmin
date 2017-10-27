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
 * Represents a guild's text channel.
 */
class TextChannel extends TextBasedChannel
    implements \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface { //TODO: Implementation
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    protected $guild;
    
    protected $parentID;
    protected $name;
    protected $topic;
    protected $nsfw;
    protected $position;
    protected $permissionsOverwrites;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client, $channel);
        $this->guild = $guild;
        
        $this->permissionsOverwrites = new \CharlotteDunois\Yasmin\Models\Collection();
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->topic = $channel['topic'] ?? $this->topic ?? '';
        $this->nsfw = $channel['nsfw'] ?? $this->nsfw ?? false;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        
        if(!empty($channel['permissions_overwrites'])) {
            foreach($channel['permissions_overwrites'] as $permission) {
                $this->permissionsOverwrites->set($permission['id'], new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($client, $this, $permission));
            }
        }
    }
    
    /**
     * @inheritdoc
     * @property-read  \CharlotteDunois\Yasmin\Models\ChannelCategory|null  $parent             Returns the channel's parent, or null.
     * @property-read  bool|null                                                $permissionsLocked  If the permissionOverwrites match the parent channel, null if no parent.
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
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<#'.$this->id.'>';
    }
}
