<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\Structures;

class User {
    protected $id;
    protected $username;
    protected $discriminator;
    protected $tag;
    
    function __construct($user) {
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->discriminator = $user['discriminator'];
        $this->tag = $this->username.'#'.$this->discriminator;
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        return NULL;
    }
}
