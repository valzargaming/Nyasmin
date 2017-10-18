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
 * Represents the Client User.
 */
class ClientUser extends User { //TODO: Implementation
    /**
     * @access private
     */
    function __construct($client, $user) {
        parent::__construct($client, $user);
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Set your status.
     * @param string $status  Valid values are: online, idle, dnd and offline.
     * @return null
     */
    function setStatus(string $status) {
        $presence = array(
            'status' => $status
        );
        
        return $this->setPresence($presence);
    }
    
    /**
     * Set your playing game.
     * @param string $name  The game name.
     * @param string $url   If you're streaming, this is the url to the stream.
     * @return null
     */
    function setGame(string $name, string $url = '') {
        $status = null;
        
        $previous = $this->presence;
        if($previous) {
            $status = $previous->getStatus();
        }
        
        $presence = array(
            'status' => $status,
            'game' => array(
                'name' => $name,
                'type' => 0,
                'url' => null
            )
        );
        
        if(!empty($url)) {
            $presence['game']['type'] = 1;
            $presence['game']['url'] = $url;
        }
        
        return $this->setPresence($presence);
    }
    
    /**
     * Set your presence.
     * @param array $presence
     * @return null
     */
    function setPresence(array $presence) {
        $packet = array(
            'op' => \CharlotteDunois\Yasmin\Constants::OPCODES['STATUS_UPDATE'],
            'd' => $presence
        );
        
        if(!array_key_exists('game', $packet['d'])) {
            $packet['d']['game'] = null;
        }
        
        return $this->client->wsmanager()->send($packet);
    }
}
