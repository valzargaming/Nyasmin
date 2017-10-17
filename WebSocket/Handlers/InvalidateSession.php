<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

class InvalidateSession {
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle($packet) {
        $this->wshandler->client->getLoop()->addTimer(2, function () {
            $this->wshandler->wsmanager->sendIdentify('IDENTIFY');
        });
    }
}
