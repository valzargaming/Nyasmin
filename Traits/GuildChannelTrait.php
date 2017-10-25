<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Traits;

/**
 * The trait all guild channels use.
 */
trait GuildChannelTrait {
    /**
     * Edits the channel. Options are as following (all are optional, but at least one is required).
     *
     *  array(
     *    'name' => string,
     *    'position' => int,
     *    'topic' => string,
     *    'bitrate' => int, (voice channels only)
     *    'userLimit' => int (voice channels only)
     *  )
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \Exception
     */
    function edit(array $options, string $reason = '') {
        if(empty($options)) {
            throw new \Exception('Can not edit channel with zero information');
        }
        
        $data = array();
        
        if(isset($options['name'])) {
            if(empty($options['name']) || !\is_string($options['name'])) {
                throw new \Exception('Can not set channel name to empty');
            }
            
            $data['name'] = $options['name'];
        }
        
        if(isset($options['position'])) {
            if(!\is_int($options['positon'])) {
                throw new \Exception('Can not set channel position to something non-integer');
            }
            
            $data['position'] = $options['position'];
        }
        
        if(isset($options['bitrate'])) {
            if($this instanceof \CharlotteDunois\Yasmin\Structures\TextChannel) {
                throw new \Exception('Can not set channel bitrate of a text channel');
            }
            
            if(!\is_int($options['bitrate'])) {
                throw new \Exception('Can not set channel bitrate to something non-integer');
            }
            
            $data['bitrate'] = $options['bitrate'];
        }
        
        if(isset($options['userLimit'])) {
            if($this instanceof \CharlotteDunois\Yasmin\Structures\TextChannel) {
                throw new \Exception('Can not set channel user limit of a text channel');
            }
            
            if(!\is_int($options['userLimit'])) {
                throw new \Exception('Can not set channel user limit to something non-integer');
            }
            
            $data['user_limit'] = $options['userLimit'];
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($data, $reason) {
            $this->client->apimanager()->endpoints->channel->editChannel($this->id, $data, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Deletes the channel.
     * @param string  $reason
     * @return \React\Promise\Promise<void>
     */
    function delete(string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->channel->deleteChannel($this->id, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Returns a Collection of invites.
     * @return \React\Promise\Promise<\CharlotteDunois\Collect\Collection>
     */
    function getInvites() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->getChannelInvites($this->id)->then(function ($data) use ($resolve) {
                $collection = new \CharlotteDunois\Collect\Collection();
                
                foreach($data as $invite) {
                    
                }
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
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($overwrites, $reason) {
            $promises = array();
            foreach($overwrites as $overwrite) {
                $promises[] = $this->client->apimanager()->endpoints->guild->editChannelPermissions($this->id, $overwrite['id'], $overwrite, $reason);
            }
            
            \React\Promise\all($promises)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Sets the name of the channel.
     * @param string  $name
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \Exception
     */
     function setName(string $name, string $reason = '') {
         return $this->edit(array('name' => $name), $reason);
     }
    
    /**
     * Sets the position of the channel.
     * @param int     $position
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \Exception
     */
    function setPosition(int $position, string $reason = '') {
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
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($newPositions, $reason) {
            $this->client->apimanager()->endpoints->guild->modifyGuildChannelPositions($this->guild->id, $newPositions, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Sets the topic of the channel.
     * @param string  $topic
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \Exception
     */
     function setTopic(string $topic, string $reason = '') {
         return $this->edit(array('topic' => $topic), $reason);
     }
}
