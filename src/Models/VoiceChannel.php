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
 * Represents a guild's voice channel.
 *
 * @property  string                                                                                   $id                     The ID of the channel.
 * @property  string                                                                                   $type                   The type of the channel. ({@see \CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES})
 * @property  int                                                                                      $createdTimestamp       When this channel was created.
 * @property  string                                                                                   $name                   The name of the channel.
 * @property  int                                                                                      $bitrate                The bitrate of the channel.
 * @property  \CharlotteDunois\Yasmin\Utils\Collection                                                 $members                Holds all members which currently are in the voice channel. ({@see \CharlotteDunois\Yasmin\Models\GuildMember})
 * @property  string|null                                                                              $parentID               The ID of the parent channel, or null.
 * @property  int                                                                                      $position               The position of the channel.
 * @property \CharlotteDunois\Yasmin\Utils\Collection                                                  $permissionOverwrites   A collection of PermissionOverwrite instances.
 * @property  int                                                                                      $userLimit              The maximum amount of users allowed in the channel - 0 means unlimited.
 *
 * @property  bool                                                                                     $full                   Checks if the voice channel is full.
 * @property  \CharlotteDunois\Yasmin\Models\Guild                                                     $guild                  The guild the channel is in.
 * @property  \CharlotteDunois\Yasmin\Models\CategoryChannel|null                                      $parent                 Returns the channel's parent, or null.
 * @property  bool|null                                                                                $permissionsLocked      DEPRECATED: If the permissionOverwrites match the parent channel, or null if no parent.
 * @property  bool                                                                                     $speakable              DEPRECATED: Whether the client has permission to send audio to the channel.
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
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES[$channel['type']];
        $this->members = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->permissionOverwrites = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        
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
            case 'full':
                return ($this->userLimit > 0 && $this->userLimit <= $this->members->count());
            break;
            case 'parent':
                return $this->guild->channels->get($this->parentID);
            break;
            case 'permissionsLocked': // TODO: DEPRECATED
                return $this->isPermissionsLocked();
            break;
            case 'speakable': // TODO: DEPRECATED
                return $this->canSpeak();
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Whether the client user can speak in this channel.
     * @return bool
     */
    function canSpeak() {
        return $this->permissionsFor($this->guild->me)->has(\CharlotteDunois\Yasmin\Models\Permissions::PERMISSIONS['SPEAK']);
    }
    
    /**
     * Sets the bitrate of the channel. Resolves with $this.
     * @param int     $bitrate
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    function setBitrate(int $bitrate, string $reason = '') {
        return $this->edit(array('bitrate' => $bitrate), $reason);
    }
    
    /**
     * Sets the user limit of the channel. Resolves with $this.
     * @param int     $userLimit
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    function setUserLimit(int $userLimit, string $reason = '') {
        return $this->edit(array('userLimit' => $userLimit), $reason);
    }
    
    /**
     * Automatically converts to the name.
     * @return string
     */
    function __toString() {
        return $this->name;
    }
    
    /**
     * @return void
     * @internal
     */
    function _patch(array $channel) {
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->bitrate = $channel['bitrate'] ?? $this->bitrate ?? 0;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        $this->userLimit = $channel['user_limit'] ?? $this->userLimit ?? 0;
        
        if(isset($channel['permission_overwrites'])) {
            $this->permissionOverwrites->clear();
            
            foreach($channel['permission_overwrites'] as $permission) {
                $overwrite = new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($this->client, $this, $permission);
                $this->permissionOverwrites->set($overwrite->id, $overwrite);
            }
        }
    }
}
