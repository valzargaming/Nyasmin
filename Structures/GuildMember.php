<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class GuildMember extends Structure
    implements \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface { //TODO: Implementation
    
    protected $guild;
    protected $id;
    
    function __construct($client, $guild, $user) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->id = $user['id'];
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return NULL;
    }
    
    function __toString() {
        return '<@'.($this->nickname ? '!' : '').$this->id.'>';
    }
}
