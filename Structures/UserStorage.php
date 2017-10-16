<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class UserStorage extends Collection { //TODO: Docs
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
    }
    
    function client() {
        return $this->client;
    }
    
    function resolve($user) {
        if($user instanceof \CharlotteDunois\Yasmin\Structures\User) {
            return $user;
        }
        
        if(is_string($user) && $this->has($user)) {
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
        
        $user = new \CharlotteDunois\Yasmin\Structures\User($this->client, $user);
        $this->set($user->id, $user);
        
        return $user;
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
}
