<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class VoiceChannel extends TextBasedChannel { //TODO: Implementation
    protected $guild;
    
    protected $bitrate;
    protected $members;
    protected $parentID;
    protected $position;
    protected $permissionsOverwrites;
    protected $userLimit;
    
    function __construct($client, $guild, $channel) {
        parent::__construct($client, $channel);
        $this->guild = $guild;
        
        $this->members = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->bitrate = $channel['bitrate'];
        $this->name = $channel['name'];
        $this->parentID = $channel['parent_id'] ?? null;
        $this->position = $channel['position'];
        $this->permissionsOverwrites = $channel['permissions_overwrites'];
        $this->userLimit = $channel['user_limit'];
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
        return '<#'.$this->id.'>';
    }
}
