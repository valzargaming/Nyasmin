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
 * Something all storages implement. The storage also is used as factory.
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
     * Returns a copy of itself.
     * @return StorageInterface|\CharlotteDunois\Yasmin\Utils\Collection
     */
    function copy() {
        return (new self($this->data));
    }
    
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
     * Filters the storage by a given callback, keeping only those items that pass a given truth test. Returns a new Storage instance (or Collection).
     * @param callable  $closure
     * @return StorageInterface|\CharlotteDunois\Yasmin\Utils\Collection
    */
    function filter(callable $closure);
    
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
     * Sorts the storage by the given key in descending order. Returns a new Storage instance (or Collection).
     * @param mixed|\Closure  $sortkey
     * @param int             $options
     * @return StorageInterface|\CharlotteDunois\Yasmin\Utils\Collection
    */
    function sortByDesc($sortkey, $options = \SORT_REGULAR);
    
    /**
     * Return the maximum value of a given key.
     * @param mixed  $key
     * @return int
    */
    function max($key = '');
}
