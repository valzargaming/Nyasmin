<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Collection extends \CharlotteDunois\Collect\Collection { //TODO: Docs
    protected $data = array();
    
    function __construct(array $data = null) {
        return \CharlotteDunois\Collect\Collection::create($data);
    }
    
    function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    function delete($key) {
        return $this->forget($key);
    }
}
