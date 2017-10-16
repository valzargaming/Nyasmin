<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

class Ready {
    protected $client;
    
    function __construct($client) {
        $this->client = $client;
    }
    
    function handle($data) { //TODO: Implementation completify
        $this->client->setClientUser($data['user']);
        $this->client->wsmanager()->setSessionID($data['session_id']);
        
        $this->client->wsmanager()->emit('ready');
    }
}
