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
 * Represents a guild's text channel.
 */
class TextChannel extends TextBasedChannel
    implements \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface { //TODO: Implementation
    
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
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Structures\Guild $guild, array $channel) {
        parent::__construct($client, $channel);
        $this->guild = $guild;
        
        $this->permissionsOverwrites = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->topic = $channel['topic'] ?? $this->topic ?? '';
        $this->nsfw = $channel['nsfw'] ?? $this->nsfw ?? false;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        
        if(!empty($channel['permissions_overwrites'])) {
            foreach($channel['permissions_overwrites'] as $permission) {
                $this->permissionsOverwrites->set($permission['id'], new \CharlotteDunois\Yasmin\Structures\PermissionOverwrite($client, $this, $permission));
            }
        }
    }
    
    /**
     * @inheritdoc
     * @property-read  \CharlotteDunois\Yasmin\Structures\ChannelCategory|null  $parent             Returns the channel's parent, or null.
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
     * Edits the channel.
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \Exception
     */
    function edit(array $options, string $reason = '') {
        
    }
    
    /**
     * Deletes the channel.
     * @param string  $reason
     * @return \React\Promise\Promise<void>
     * @throws \Exception
     */
    function delete(string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->channel->deleteChannel($this->id, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Returns the permissions for the given member.
     * @param \CharlotteDunois\Yasmin\Structures\GuildMember|string  $member
     * @return \CharlotteDunois\Yasmin\Structures\Permissions
     * @throws \Exception
     */
    function permissionsFor($member) {
        $member = $this->guild->members->resolve($member);
        
        if($member->id === $this->guild->ownerID) {
            return (new \CharlotteDunois\Yasmin\Structures\Permissions($this->client, \CharlotteDunois\Yasmin\Structures\Permissions::ALL));
        }
        
        $maxBitfield = $member->roles->map(function ($role) {
            return $role->permissions->bitfield;
        })->max();
        
        $permissions = new \CharlotteDunois\Yasmin\Structures\Permissions($this->client, $maxBitfield);
        
        if($permissions->has('ADMINISTRATOR')) {
            return (new \CharlotteDunois\Yasmin\Structures\Permissions($this->client, \CharlotteDunois\Yasmin\Structures\Permissions::ALL));
        }
        
        $overwrites = $this->overwritesFor($member);
        
        if($overwrites['everyone']) {
            if($overwrites['everyone']->allow) {
                $permissions->add($overwrites['everyone']->allow->bitfield);
            }
            
            if($overwrites['everyone']->deny) {
                $permissions->remove($overwrites['everyone']->deny->bitfield);
            }
        }
        
        if($overwrites['member']) {
            if($overwrites['member']->allow) {
                $permissions->add($overwrites['member']->allow->bitfield);
            }
            
            if($overwrites['member']->deny) {
                $permissions->remove($overwrites['member']->deny->bitfield);
            }
        }
        
        foreach($overwrites['roles'] as $role) {
            if($role->allow) {
                $permissions->add($role->allow);
            }
            if($role->deny) {
                $permissions->remove($role->deny);
            }
        }
        
        return $permissions;
    }
    
    /**
     * Returns the permissions overwrites for the given member.
     * @param \CharlotteDunois\Yasmin\Structures\GuildMember|string  $member
     * @return array
     * @throws \Exception
     */
    function overwritesFor($member) {
        $member = $this->guild->members->resolve($member);
        
        $everyoneOverwrites = null;
        $memberOverwrites = null;
        $rolesOverwrites = array();
        
        foreach($this->permissionsOverwrites->all() as $overwrite) {
            if($overwrite->id === $this->guild->id) {
                $everyoneOverwrites = $overwrite;
            } elseif($overwrite->id === $member->id) {
                $memberOvewrites = $overwrite;
            } elseif($member->roles->has($overwrite->id)) {
                $rolesOverwrites[] = $overwrite;
            }
        }
        
        return array(
            'everyone' => $everyoneOverwrites,
            'member' => $memberOverwrites,
            'roles' => $rolesOverwrites
        );
    }
    /**
     * Overwrites the permissions for a user or role in this channel.
     * @param \CharlotteDunois\Yasmin\Structures\GuildMember|string  $member
     * @param array                                                  $options
     * @param string                                                 $reason
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Structures\PermissionOverwite>
     * @throws \Exception
     */
    function overwritePermissions($member, array $options, string $reason = '') {
        $member = $this->guild->members->resolve($member);
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($member, $options, $reason) {
            
        }));
    }
    
    /**
     * Locks in the permission overwrites from the parent channel.
     * @return \React\Promise\Promise<this>
     * @throws \Exception
     */
    function lockPermissions() {
        if(!$this->__get('parent')) {
            throw new \Exception('This channel does not have a parent');
        }
        
        $overwrites = \array_values($this->__get('parent')->permissionsOverwrites->map(function ($overwrite) {
            return array(
                'id' => $overwrite->id,
                'type' => $overwrite->type,
                'allow' => $overwrite->allow->bitfield,
                'deny' => $overwrite->deny->bitfield
            );
        })->all());
        
        return $this->edit(array('permissionsOverwrites' => $overwrites));
    }
    
    /**
     * Sets the position of the category.
     * @param int     $position
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     */
    function setPosition(int $position, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($position, $reason) {
            if($position < 0 ) {
                throw new \Exception('Position can not be below 0');
            }
            
            $newPositions = array();
            $newPositions[] = array('id' => $this->id, 'position' => $position);
            
            $count = $this->guild->channels->count();
            $channels = $this->guild->channels->sort(function ($a, $b) {
                return $a->position <=> $b->position;
            })->all();
            
            $pos = 0;
            for($i = 0; $i < $count; $i++) {
                if($pos === $position) {
                    $pos++;
                }
                
                $channel = $channels[$i];
                
                if($pos === $channel->position) {
                    continue;
                }
                
                $newPositions[] = array('id' => $channel, 'position' => $pos);
                $pos++;
            }
            
            $this->client->apimanager()->endpoints->guild->modifyGuildChannelPositions($this->guild->id, $newPositions, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<#'.$this->id.'>';
    }
}
