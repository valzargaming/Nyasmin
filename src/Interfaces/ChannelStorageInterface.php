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
 * Something all channel storages implement. The storage also is used as factory.
 */
interface ChannelStorageInterface extends StorageInterface {
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param string  $key
     * @return \CharlotteDunois\Yasmin\Interfaces\ChannelInterface|null
     * @throws \InvalidArgumentException
    */
    function get($key);
    
    /**
     * Sets a key-value pair.
     * @param string                                               $key
     * @param \CharlotteDunois\Yasmin\Interfaces\ChannelInterface  $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    function set($key, $value);
}
