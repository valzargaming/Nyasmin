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
 * Something all storages implement.
 */
interface StorageInterface extends \Countable, \Iterator {
    /**
     * Returns the current element. From Iterator interface.
     * @return mixed
     */
    function current();
    
    /**
     * Fetch the key from the current element. From Iterator interface.
     * @return mixed
     */
    function key();
    
    /**
     * Advances the internal pointer. From Iterator interface.
     * @return mixed|false
     */
    function next();
    
    /**
     * Resets the internal pointer. From Iterator interface.
     * @return mixed|false
     */
    function rewind();
    
    /**
     * Checks if current position is valid. From Iterator interface.
     * @return bool
     */
    function valid();
    
    /**
     * Returns all items.
     * @return mixed[]
     */
    function all();
    
    /**
     * Returns the total number of items. From Countable interface.
     * @return int
    */
    function count();
    
    /**
     * Determines if a given key exists in the collection.
     * @param mixed  $key
     * @return bool
     * @throws \InvalidArgumentException
    */
    function has($key);
    
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param mixed  $key
     * @return mixed|null
     * @throws \InvalidArgumentException
    */
    function get($key);
    
    /**
     * Sets a key-value pair.
     * @param mixed  $key
     * @param mixed  $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    function set($key, $value);
    
    /**
     * Removes an item.
     * @param mixed  $key
     * @return $this
    */
    function delete($key);
    
    /**
     * Clears the Storage.
     * @return $this
     */
    function clear();
    
    /**
     * Returns the first element that passes a given truth test.
     * @param callable|null  $closure
     * @return mixed|null
    */
    function first(?callable $closure = null);
    
    /**
     * Returns the last element that passes a given truth test.
     * @param callable|null  $closure
     * @return mixed|null
    */
    function last(?callable $closure = null);
    
    /**
     * Return the maximum value of a given key.
     * @param mixed  $key
     * @return int
    */
    function max($key = '');
}
