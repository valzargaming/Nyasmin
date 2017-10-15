<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket\Handlers;

class RequestGuildMembers {
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle($packet) {
        
    }
}
