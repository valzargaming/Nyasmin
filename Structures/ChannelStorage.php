<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class ChannelStorage extends Collection { //TODO: Docs
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
    }
    
    function client() {
        return $this->client;
    }
    
    function resolve($channel) {
        if($channel instanceof \CharlotteDunois\Yasmin\Interfaces\ChannelInterface) {
            return $channel;
        }
        
        if(is_string($channel) && $this->has($channel)) {
            return $this->get($channel);
        }
        
        throw new \Exception('Unable to resolve unknown channel');
    }
    
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->channels) {
            $this->client->channels->set($key, $value);
        }
        
        return $this;
    }
    
    function delete($key) {
        parent::forget($key);
        if($this !== $this->client->channels) {
            $this->client->channels->delete($key);
        }
        
        return $this;
    }
}
