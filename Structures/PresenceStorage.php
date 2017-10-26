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
 * @access private
 */
class PresenceStorage extends Collection
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
    
    function resolve($presence) {
        if($presence instanceof \CharlotteDunois\Yasmin\Structures\Presence) {
            return $presence;
        }
        
        if(\is_string($presence) && $this->has($presence)) {
            return $this->get($presence);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown presence');
    }
    
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->presences) {
            $this->client->presences->set($key, $value);
        }
        
        return $this;
    }
    
    function delete($key) {
        parent::forget($key);
        if($this !== $this->client->presences) {
            $this->client->presences->delete($key);
        }
        
        return $this;
    }
    
    function factory(array $data) {
        $presence = new \CharlotteDunois\Yasmin\Structures\Presence($this->client, $data);
        $this->set($data['user']['id'], $presence);
        return $presence;
    }
}
