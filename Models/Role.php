<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

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
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $role) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->members = new \CharlotteDunois\Yasmin\Models\Collection();
        
        $this->id = $role['id'];
        $this->name = $role['name'];
        $this->color = $role['color'];
        $this->hoist = $role['hoist'];
        $this->position = $role['position'];
        $this->permissions = new \CharlotteDunois\Yasmin\Models\Permissions($role['permissions']);
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
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<@&'.$this->id.'>';
    }
}
