<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Role extends Structure { //TODO: Implementation
    protected $guild;
    
    protected $id;
    protected $name;
    protected $color;
    protected $hoist;
    protected $members;
    protected $position;
    protected $permissions;
    protected $managed;
    protected $mentionable;
    
    function __construct($client, $guild, $role) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->members = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->id = $role['id'];
        $this->name = $role['name'];
        $this->color = $role['color'];
        $this->hoist = $role['hoist'];
        $this->position = $role['position'];
        $this->permissions = new \CharlotteDunois\Yasmin\Structures\Permissions($client, $role['permissions']);
        $this->managed = $role['managed'];
        $this->mentionable = $role['mentionable'];
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return null;
    }
    
    function __toString() {
        return '<@'.($this->nickname ? '!' : '').$this->id.'>';
    }
}
