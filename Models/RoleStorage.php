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
 * Role Storage to store a guild's roles, utilizes Collection.
 * @todo Docs
 */
class RoleStorage extends Storage {
    protected $guild;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $data = null) {
        parent::__construct($client, $data);
        $this->guild = $guild;
    }
    
    /**
     * Resolves given data to a Role.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role  string = role ID
     * @return \CharlotteDunois\Yasmin\Models\Role
     * @throws \InvalidArgumentException
     */
    function resolve($role) {
        if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
            return $role;
        }
        
        if(\is_string($role) && $this->has($role)) {
            return $this->get($role);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown role');
    }
    
    /**
     * @internal
     */
    function factory(array $data) {
        $role = new \CharlotteDunois\Yasmin\Models\Role($this->client, $this->guild, $data);
        $this->set($role->id, $role);
        return $role;
    }
}
