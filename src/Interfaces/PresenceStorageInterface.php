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
 * Something all presence storages implement. The storage also is used as factory.
 */
interface PresenceStorageInterface extends StorageInterface {
    /**
     * Resolves given data to a presence.
     * @param \CharlotteDunois\Yasmin\Models\Presence|\CharlotteDunois\Yasmin\Models\User|string|int  $presence  string/int = user ID
     * @return \CharlotteDunois\Yasmin\Models\Presence
     * @throws \InvalidArgumentException
     */
    function resolve($presence);
    
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param string  $key
     * @return \CharlotteDunois\Yasmin\Models\Presence|null
     * @throws \InvalidArgumentException
    */
    function get($key);
    
    /**
     * Sets a key-value pair.
     * @param string                                   $key
     * @param \CharlotteDunois\Yasmin\Models\Presence  $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    function set($key, $value);
    
    /**
     * Factory to create presences.
     * @param array  $data
     * @return \CharlotteDunois\Yasmin\Models\Presence
     * @internal
     */
    function factory(array $data);
}
