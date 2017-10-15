<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Structure { //TODO
    protected $client;
    
    function __construct($client) {
        $this->client = $client;
    }
    
    function client() {
        return $this->client;
    }
}
