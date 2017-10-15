<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket\Handlers;

class Heartbeat {
    public $heartbeat;
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle($packet) {
        $this->wshandler->getWSManager()->heartbeatAck();
    }
}
