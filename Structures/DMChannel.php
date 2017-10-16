<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class DMChannel extends TextChannel { //TODO: Implementation
    protected $type = 'dm';
    
    function __construct($client, $channel) {
        parent::__construct($client, $channel);
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return NULL;
    }
}
