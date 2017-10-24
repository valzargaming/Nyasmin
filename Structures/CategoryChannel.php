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
 * Represents a guild's category channel.
 */
class CategoryChannel extends TextBasedChannel { //TODO: Implementation
    protected $guild;
    
    protected $name;
    protected $parentID;
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
     * @property-read \CharlotteDunois\Yasmin\Structures\Collection  $children  Returns all channels which are childrens of this category.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'children':
                return $this->guilds->channels->filter(function ($channel) {
                    return $channel->parentID === $this->id;
                });
            break;
        }
        
        return null;
    }
    
    /**
     * Returns the permissions for the given member.
     * @param \CharlotteDunois\Yasmin\Structures\GuildMember|string  $member
     * @return \CharlotteDunois\Yasmin\Structures\Permissions
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
}
