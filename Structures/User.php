<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\Structures;

class User extends Structure { //TODO
    protected $id;
    protected $username;
    protected $discriminator;
    protected $tag;
    protected $verified;
    
    function __construct($user) {
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->discriminator = $user['discriminator'];
        $this->tag = $this->username.'#'.$this->discriminator;
        $this->verified = $user->verified;
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        return NULL;
    }
}
