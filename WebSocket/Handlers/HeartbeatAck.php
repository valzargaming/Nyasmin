<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket\Handlers;

class HeartbeatAck {
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle() {
        $end = microtime(true);
        $this->wshandler->getWSManager()->getClient()->_pong($end);
        
        $this->wshandler->getWSManager()->wsHeartbeat['ack'] = true;
        $this->wshandler->getWSManager()->wsHeartbeat['dateline'] = 0;
    }
}
