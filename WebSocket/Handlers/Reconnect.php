<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

class Reconnect {
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle($packet) { //TODO
        $this->wshandler->wsmanager()->disconnect();
        $this->wshandler->wsmanager()->sendIdentify('IDENTIFY');
    }
}
