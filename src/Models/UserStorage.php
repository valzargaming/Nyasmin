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
 * User Storage to store and cache users, which utlizies Collection.
 */
class UserStorage extends Storage {
    /**
     * @var \React\EventLoop\TimerInterface|\React\EventLoop\Timer\TimerInterface
     */
    protected $timer;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $data = null) {
        parent::__construct($client, $data);
        
        $inv = (int) $this->client->getOption('userSweepInterval', 600);
        if($inv > 0) {
            $this->timer = $this->client->addPeriodicTimer($inv, function () {
                $this->sweep();
            });
        }
    }
    
    /**
     * Resolves given data to an user.
     * @param \CharlotteDunois\Yasmin\Models\User|\CharlotteDunois\Yasmin\Models\GuildMember|string|int  $user  string/int = user ID
     * @return \CharlotteDunois\Yasmin\Models\User
     * @throws \InvalidArgumentException
     */
    function resolve($user) {
        if($user instanceof \CharlotteDunois\Yasmin\Models\User) {
            return $user;
        }
        
        if($user instanceof \CharlotteDunois\Yasmin\Models\GuildMember) {
            return $user->user;
        }
        
        if(\is_int($user)) {
            $user = (string) $user;
        }
        
        if(\is_string($user) && $this->has($user)) {
            return $this->get($user);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown user');
    }
    
    /**
     * @internal
     */
    function patch(array $user) {
        if($this->has($user['id'])) {
            return $this->get($user['id']);
        }
        
        if(count($user) === 1) {
            return null;
        }
        
        return $this->factory($user);
    }
    
    /**
     * Returns the item for a given key. If the key does not exist, null is returned.
     * @param mixed  $key
     * @return \CharlotteDunois\Yasmin\Models\User|null
     */
    function get($key) {
        return parent::get($key);
    }
    
    /**
     * {@inheritdoc}
     */
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->users) {
            $this->client->users->set($key, $value);
        }
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    function delete($key) {
        parent::delete($key);
        if($this !== $this->client->users) {
            $this->client->users->delete($key);
        }
        
        return $this;
    }
    
    /**
     * @internal
     */
    function factory(array $data, bool $userFetched = false) {
        if($this->has($data['id'])) {
            $user = $this->get($data['id']);
            $user->_patch($data);
            return $user;
        }
        
        $user = new \CharlotteDunois\Yasmin\Models\User($this->client, $data, false, $userFetched);
        $this->set($user->id, $user);
        
        return $user;
    }
    
    /**
     * Sweeps users falling out of scope (no mutual guilds). Returns the amount of sweeped users.
     * @return int
     */
    function sweep() {
        $members = \array_unique($this->client->guilds->reduce(function ($carry, $g) {
            return \array_merge($carry, \array_keys($g->members->all()));
        }, array()));
        
        $amount = 0;
        foreach($this->data as $key => $val) {
            if($val->id !== $this->client->user->id && !$val->userFetched && !\in_array($key, $members, true)) {
                $this->delete($key);
                unset($val);
                
                $amount++;
            }
        }
        
        return $amount;
    }
}
