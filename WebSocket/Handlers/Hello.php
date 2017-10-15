<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket\Handlers;

class Hello {
    public $heartbeat = NULL;
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
        
        $this->wshandler->wsmanager()->on('close', function () {
            $this->close();
        });
    }
    
    function handle($packet) {
        $interval = $packet['d']['heartbeat_interval'] / 1000;
        
        $this->heartbeat = $this->wshandler->client()->getLoop()->addPeriodicTimer($interval, function () {
            $this->wshandler->wsmanager()->heartbeat();
        });
    }
    
    private function close() {
        if($this->heartbeat !== NULL) {
            $this->wshandler->client()->getLoop()->cancelTimer($this->heartbeat);
            $this->heartbeat = NULL;
        }
    }
}
