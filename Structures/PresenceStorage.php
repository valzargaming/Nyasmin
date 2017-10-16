<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class PresenceStorage extends Collection { //TODO: Docs
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
    }
    
    function client() {
        return $this->client;
    }
    
    function resolve($presence) {
        if($presence instanceof \CharlotteDunois\Yasmin\Structures\Presence) {
            return $presence;
        }
        
        if(is_string($presence) && $this->has($presence)) {
            return $this->get($presence);
        }
        
        throw new \Exception('Unable to resolve unknown presence');
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
}
