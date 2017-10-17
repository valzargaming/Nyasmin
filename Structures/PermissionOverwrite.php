<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class PermissionOverwite extends Structure { //TODO: Implementation
    protected $channel;
    
    protected $id;
    protected $type;
    protected $target;
    protected $allow;
    protected $deny;
    
    function __construct($client, $channel, $permission) {
        parent::__construct($client);
        $this->channel = $channel;
        
        $this->id = $permission['id'];
        $this->type = $permission['type'] ?? $this->type;
        $this->target = ($this->type === 'role' ? $this->channel->guild->roles->get($permission['id']) : $this->channel->guild->members->get($permission['id']));
        $this->allow = (!empty($permission['allow']) ? (new \CharlotteDunois\Yasmin\Structures\Permissions($client, $permission['allow'])) : $this->allow);
        $this->deny = (!empty($permission['deny']) ? (new \CharlotteDunois\Yasmin\Structures\Permissions($client, $permission['deny'])) : $this->deny);
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'guild':
                return $this->channel->guild;
            break;
        }
        
        return null;
    }
    
    function __toString() {
        return '<@'.($this->nickname ? '!' : '').$this->id.'>';
    }
}
