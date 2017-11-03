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
class RoleStorage extends \CharlotteDunois\Yasmin\Utils\Collection
    implements \CharlotteDunois\Yasmin\Interfaces\StorageInterface { //TODO: Docs
    
    protected $client;
    protected $guild;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $data = null) {
        parent::__construct($data);
        $this->client = $client;
        $this->guild = $guild;
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
        }
        
        return parent::__get($name);
    }
    
    function resolve($role) {
        if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
            return $role;
        }
        
        if(\is_string($role) && $this->has($role)) {
            return $this->get($role);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown role');
    }
    
    function factory(array $data) {
        $role = new \CharlotteDunois\Yasmin\Models\Role($this->client, $this->guild, $data);
        $this->set($role->id, $role);
        return $role;
    }
}
