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
     * The manual creation of such an object is discouraged. There may be an easy and safe way to create such an object in the future.
     * @param \CharlotteDunois\Yasmin\Client  $client  The client this object is for.
     * @param array                           $game    An array containing user (as array, with an element id), game (as array) and status.
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $presence) {
        parent::__construct($client);
        
        $this->user = $client->users->get($presence['user']['id']);
        $this->game = (!empty($presence['game']) ? (new \CharlotteDunois\Yasmin\Structures\Game($client, $presence['game'])) : null);
        $this->status = $presence['status'];
    }
    
    /**
     * @property-read \CharlotteDunois\Yasmin\Structures\User        $user    The user this presence belongs to.
     * @property-read \CharlotteDunois\Yasmin\Structures\Game|null   $game    The game the user is playing.
     * @property-read string                                         $status  What do you expect this to be?
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
    
    /**
     * @access private
     */
     function jsonSerialize() {
         return array(
             'status' => $this->status,
             'game' => $this->game
         );
     }
}
