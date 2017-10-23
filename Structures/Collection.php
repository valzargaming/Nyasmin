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
    /**
     * I think you are supposed to know what this does.
     * @param array|null $data
     */
    function __construct(array $data = null) {
        if(!empty($data)) {
            parent::__construct($data);
        }
    }
    
    function __debugInfo() {
        return $this->data;
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
    
    /**
     * Clears the Collection.
     * @return this
     */
    function clear() {
        $this->data = array();
        return $this;
    }
}
