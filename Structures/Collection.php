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
 * Util to store a collection.
 */
class Collection extends \CharlotteDunois\Collect\Collection { //TODO: Docs
    protected $data = array();
    
    /**
     * I think you are supposed to know what this does.
     * @param array|null $data
     */
    function __construct(array $data = null) {
        return \CharlotteDunois\Collect\Collection::create($data);
    }
    
    /**
     * Sets a new key-value pair (or overwrites an existing key-value pair).
     * @param string $key
     * @param mixed $value
     * @return this
     */
    function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Removes a specific key-value pair.
     * @param string $key
     * @return this
     */
    function delete($key) {
        return $this->forget($key);
    }
}
