<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Presence extends Structure { //TODO
    protected $user;
    protected $game;
    protected $status;
    
    function __construct($client, $presence) {
        parent::__construct($client);
        
        $this->user = $this->client->users->patch($presence['user']);
        $this->game = (!empty($presence['game']) ? (new \CharlotteDunois\Yasmin\Structures\Game($client, $presence['game'])) : null);
        $this->status = $presence['status'];
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
}
