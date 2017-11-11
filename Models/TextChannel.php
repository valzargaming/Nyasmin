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
 *
 * @property  \CharlotteDunois\Yasmin\Models\Guild                                                    $guild                  The associated guild.
 * @property  string                                                                                  $name                   The channel name.
 * @property  string                                                                                  $topic                  The channel topic.
 * @property  bool                                                                                    $nsfw                   Whether the channel is marked as NSFW or not.
 * @property  string|null                                                                             $parentID               The ID of the parent channel, or null.
 * @property  int                                                                                     $position               The channel position.
 * @property \CharlotteDunois\Yasmin\Utils\Collection                                                 $permissionOverwrites   A collection of PermissionOverwrite objects.
 *
 * @property  \CharlotteDunois\Yasmin\Models\ChannelCategory|null                                     $parent                 Returns the channel's parent, or null.
 * @property  bool|null                                                                               $permissionsLocked      If the permissionOverwrites match the parent channel, null if no parent.
 */
class TextChannel extends TextBasedChannel
    implements \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface {
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    protected $guild;
    
    protected $parentID;
    protected $name;
    protected $topic;
    protected $nsfw;
    protected $position;
    protected $permissionOverwrites;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client, $channel);
        $this->guild = $guild;
        $this->_patch($channel);
    }
    
    /**
     * @inheritDoc
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
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<#'.$this->id.'>';
    }
    
    /**
     * @internal
     */
    function _patch(array $channel) {
        $this->permissionOverwrites = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->topic = $channel['topic'] ?? $this->topic ?? '';
        $this->nsfw = $channel['nsfw'] ?? $this->nsfw ?? false;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        
        if(!empty($channel['permission_overwrites'])) {
            foreach($channel['permission_overwrites'] as $permission) {
                $overwrite = new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($this->client, $this, $permission);
                $this->permissionOverwrites->set($overwrite->id, $overwrite);
            }
        }
    }
}
