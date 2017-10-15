<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\Structures;

class ClientUser extends User {
    private $data = array();
    
    function __construct($user) {
        $this->data = $user;
    }
    
    function __get($name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        } elseif(property_exists($this, $name)) {
            return $this->$name;
        }
        
        return NULL;
    }
}
