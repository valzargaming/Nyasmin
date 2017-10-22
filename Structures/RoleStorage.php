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
class RoleStorage extends Collection
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
    
    function resolve($role) {
        if($role instanceof \CharlotteDunois\Yasmin\Structures\Role) {
            return $role;
        }
        
        if(\is_string($role) && $this->has($role)) {
            return $this->get($role);
        }
        
        throw new \Exception('Unable to resolve unknown role');
    }
    
    function factory(array $data) {
        $role = new \CharlotteDunois\Yasmin\Structures\Role($this->client, $this->guild, $data);
        $this->set($role->id, $role);
        return $role;
    }
}
