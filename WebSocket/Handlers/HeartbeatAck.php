<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

/**
 * WS Event handler
 * @access private
 */
class HeartbeatAck {
    protected $wshandler;
    
    function __construct(\CharlotteDunois\Yasmin\WebSocket\WSHandler $wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle() {
        $end = microtime(true);
        
        $this->wshandler->client->emit('debug', 'Received Heartbeat Ack');
        $this->wshandler->wsmanager->_pong($end);
    }
}
