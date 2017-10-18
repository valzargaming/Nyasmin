<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Represents a presence.
 */
class Presence extends Structure { //TODO: Docs
    protected $user;
    protected $game;
    protected $status;
    
    /**
     * @access private
     */
    function __construct($client, $presence) {
        parent::__construct($client);
        
        $this->user = $this->client->users->patch($presence['user']);
        $this->game = (!empty($presence['game']) ? (new \CharlotteDunois\Yasmin\Structures\Game($client, $presence['game'])) : null);
        $this->status = $presence['status'];
    }
    
    /**
     * @property-read \CharlotteDunois\Yasmin\Structures\User  $user    The user this presence belongs to.
     * @property-read \CharlotteDunois\Yasmin\Structures\Game  $game    The game the user is playing.
     * @property-read string                                   $status  What do you expect this to be?
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
}
