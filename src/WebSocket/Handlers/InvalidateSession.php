<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

/**
 * WS Event handler
 * @internal
 */
class InvalidateSession {
    protected $wshandler;
    
    function __construct(\CharlotteDunois\Yasmin\WebSocket\WSHandler $wshandler) {
        $this->wshandler = $wshandler;
    }
    
    function handle($data) {
        if($this->wshandler->wsmanager->getSessionID() === null) {
            return $this->wshandler->wsmanager->disconnect(1006, 'Invalid OP code response to OP code 2 packet');
        }
        
        if(!$data['d']) {
            $this->wshandler->wsmanager->setSessionID(null);
        }
        
        $this->wshandler->client->getLoop()->addTimer(5, function () use ($data) {
            $this->wshandler->wsmanager->sendIdentify();
        });
    }
}
