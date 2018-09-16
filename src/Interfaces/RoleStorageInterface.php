<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * Something all role storages implement. The storage also is used as factory.
 */
interface RoleStorageInterface extends StorageInterface {
    /**
     * Resolves given data to a Role.
     * @param \CharlotteDunois\Yasmin\Models\Role|string|int  $role  string/int = role ID
     * @return \CharlotteDunois\Yasmin\Models\Role
     * @throws \InvalidArgumentException
     */
    function resolve($role);
    
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param string  $key
     * @return \CharlotteDunois\Yasmin\Models\Role|null
     * @throws \InvalidArgumentException
    */
    function get($key);
    
    /**
     * Sets a key-value pair.
     * @param string                               $key
     * @param \CharlotteDunois\Yasmin\Models\Role  $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    function set($key, $value);
    
    /**
     * Factory to create (or retrieve existing) roles.
     * @param array  $data
     * @return \CharlotteDunois\Yasmin\Models\Role
     * @internal
     */
    function factory(array $data);
}
