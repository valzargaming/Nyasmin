<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class UserStorage extends Collection
    implements \CharlotteDunois\Yasmin\Interfaces\StorageInterface { //TODO: Docs
    
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
        $this->client = $client;
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
        }
        
        return null;
    }
    
    function resolve($user) {
        if($user instanceof \CharlotteDunois\Yasmin\Structures\User) {
            return $user;
        }
        
        if(\is_string($user) && $this->has($user)) {
            return $this->get($user);
        }
        
        throw new \Exception('Unable to resolve unknown user');
    }
    
    function patch(array $user) {
        if($this->has($user['id'])) {
            return $this->get($user['id']);
        }
        
        if(count($user) === 1) {
            return null;
        }
        
        return $this->factory($user);
    }
    
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->users) {
            $this->client->users->set($key, $value);
        }
        
        return $this;
    }
    
    function delete($key) {
        parent::forget($key);
        if($this !== $this->client->users) {
            $this->client->users->delete($key);
        }
        
        return $this;
    }
    
    function factory(array $data) {
        $user = new \CharlotteDunois\Yasmin\Structures\User($this->client, $data);
        $this->set($user->id, $user);
        
        return $user;
    }
}
