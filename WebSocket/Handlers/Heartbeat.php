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
class Heartbeat {
    public $heartbeat;
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle($packet) {
        $this->wshandler->wsmanager->heartbeatAck();
    }
}
