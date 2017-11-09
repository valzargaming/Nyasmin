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
        
        $this->_patch($channel);
    }
    
    /**
     * @inheritDoc
     *
     * @property-read  string                                                                                   $id                     The ID of the channel.
     * @property-read  string                                                                                   $type                   The type of the channel.
     * @property-read  int                                                                                      $createdTimestamp       When this channel was created.
     * @property-read  string                                                                                   $name                   The name of the channel.
     * @property-read  int                                                                                      $bitrate                The bitrate of the channel.
     * @property-read  \CharlotteDunois\Yasmin\Utils\Collection<\CharlotteDunois\Yasmin\Models\GuildMember>     $members                Holds all members which currently are in the voice channel.
     * @property-read  string|null                                                                              $parentID               The ID of the parent channel, or null.
     * @property-read  int                                                                                      $position               The position of the channel.
     * @property-read  \CharlotteDunois\Collect\Collection<\CharlotteDunois\Yasmin\Models\PermissionOverwrite>  $permissionOverwrites   A collection of PermissionOverwrite objects.
     * @property-read  int                                                                                      $userLimit              The maximum amount of users allowed in the channel - 0 means unlimited.
     *
     * @property-read  \CharlotteDunois\Yasmin\Voice\VoiceConnection|null                                       $connection             he voice connection for this voice channel, if the client is connected.
     * @property-read  bool                                                                                     $full                   Checks if the voice channel is full.
     * @property-read  \CharlotteDunois\Yasmin\Models\Guild                                                     $guild                  The guild the channel is in.
     * @property-read  \CharlotteDunois\Yasmin\Models\ChannelCategory|null                                      $parent                 Returns the channel's parent, or null.
     * @property-read  bool|null                                                                                $permissionsLocked      If the permissionOverwrites match the parent channel, null if no parent.
     * @property-read  bool                                                                                     $speakable              Whether the client has permission to send audio to the channel.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'connection':
                return $this->client->voiceConnections->get($this->guild->id);
            break;
            case 'full':
                return ($this->userLimit > 0 && $this->userLimit > $this->members->count());
            break;
            case 'guild':
                return $this->channel->guild;
            break;
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
            case 'speakable':
                return $this->permissionsFor($this->channel->guild->me)->has(\CharlotteDunois\Yasmin\Constants::PERMISSIONS['SPEAK']);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Joins the voice channel.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Voice\VoiceConnection>
     * @todo Implementation of Voice
     */
    function join() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $connection = $this->__get('connection');
            if($connection) {
                return $resolve($connection);
            }
            
            $reject(new \Exception('Voice not implemented'));
        }));
    }
    
    /**
     * Leaves the voice channel.
     * @return bool
     */
    function leave() {
        $connection = $this->__get('connection');
        if($connection) {
            $connection->disconnect();
        }
        
        return true;
    }
    
    /**
     * Sets the bitrate of the channel.
     * @param int     $bitrate
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \InvalidArgumentException
     */
    function setBitrate(int $bitrate, string $reason = '') {
        return $this->edit(array('bitrate' => $bitrate), $reason);
    }
    
    /**
     * Sets the user limit of the channel.
     * @param int     $userLimit
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \InvalidArgumentException
     */
    function setUserLimit(int $userLimit, string $reason = '') {
        return $this->edit(array('userLimit' => $userLimit), $reason);
    }
    
    /**
     * @internal
     */
    function _patch(array $channel) {
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
                $overwrite = new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($this->client, $this, $permission);
                $this->permissionOverwrites->set($overwrite->id, $overwrite);
            }
        }
    }
}
