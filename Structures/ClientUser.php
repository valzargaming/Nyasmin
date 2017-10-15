<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class ClientUser extends User { //TODO
    private $data = array();
    
    function __construct($client, $user) {
        parent::__construct($client, $user);
        
        $this->data = $user;
    }
    
    function __get($name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        } elseif(property_exists($this, $name)) {
            return $this->$name;
        }
        
        return NULL;
    }
    
    function setGame(string $name, string $url = '') {
        $presence = array(
            'status' => '',
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
    
    function setPresence(array $presence) {
        $packet = array(
            'op' => \CharlotteDunois\Yasmin\Constants::$opcodes['STATUS_UPDATE'],
            'd' => $presence
        );
        
        if(!array_key_exists('game', $packet['d'])) {
            $packet['d']['game'] = null;
        }
        
        return $this->client()->wsmanager()->send($packet);
    }
}
