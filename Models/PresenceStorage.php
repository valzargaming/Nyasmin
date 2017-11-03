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
 * @todo Docs
 */
class PresenceStorage extends Storage {
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $data = null) {
        parent::__construct($client, $data);
    }
    
    function resolve($presence) {
        if($presence instanceof \CharlotteDunois\Yasmin\Models\Presence) {
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
        parent::delete($key);
        if($this !== $this->client->presences) {
            $this->client->presences->delete($key);
        }
        
        return $this;
    }
    
    function factory(array $data) {
        $presence = new \CharlotteDunois\Yasmin\Models\Presence($this->client, $data);
        $this->set($data['user']['id'], $presence);
        return $presence;
    }
}
