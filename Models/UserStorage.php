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
 * @internal
 */
class UserStorage extends \CharlotteDunois\Yasmin\Utils\Collection
    implements \CharlotteDunois\Yasmin\Interfaces\StorageInterface { //TODO: Docs
    
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $data = null) {
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
        if($user instanceof \CharlotteDunois\Yasmin\Models\User) {
            return $user;
        }
        
        if(\is_string($user) && $this->has($user)) {
            return $this->get($user);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown user');
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
        parent::delete($key);
        if($this !== $this->client->users) {
            $this->client->users->delete($key);
        }
        
        return $this;
    }
    
    function factory(array $data) {
        $user = new \CharlotteDunois\Yasmin\Models\User($this->client, $data);
        $this->set($user->id, $user);
        
        return $user;
    }
}
