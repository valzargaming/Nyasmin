<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket\Events;

class Ready {
    protected $client;
    
    function __construct($client) {
        $this->client = $client;
    }
    
    function handle($data) {
        var_dump($data);
        $this->client->setClientUser($data['user']);
        $this->client->getWSManager()->setSessionID($data['session_id']);
        
        echo 'Firing ready!'.PHP_EOL;
        try {
            $this->client->getWSManager()->emit('ready');
        } catch(\Exception $e) {
            var_dump($e->getMessage());
        }
        echo 'Ready fired!'.PHP_EOL;
    }
}
