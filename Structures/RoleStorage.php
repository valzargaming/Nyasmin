<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class RoleStorage extends Collection { //TODO: Docs
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
    }
    
    function client() {
        return $this->client;
    }
    
    function resolve($role) {
        if($role instanceof \CharlotteDunois\Yasmin\Structures\Role) {
            return $role;
        }
        
        if(is_string($role) && $this->has($role)) {
            return $this->get($role);
        }
        
        throw new \Exception('Unable to resolve unknown role');
    }
}
