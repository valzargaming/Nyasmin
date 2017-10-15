<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

class HeartbeatAck {
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle() {
        $end = microtime(true);
        $this->wshandler->client()->_pong($end);
        
        $this->wshandler->wsmanager()->wsHeartbeat['ack'] = true;
        $this->wshandler->wsmanager()->wsHeartbeat['dateline'] = 0;
    }
}
