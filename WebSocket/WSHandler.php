<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket;

class WSHandler {
    private $handlers = array();
    private $sequence = NULL;
    private $wsmanager;
    
    function __construct($wsmanager) {
        $this->wsmanager = $wsmanager;
        
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['DISPATCH'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Dispatch');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['HEARTBEAT'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Heartbeat');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['PRESENCE'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Presence');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['VOICE_STATE_UPDATE'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\VoiceStateUpdate');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['VOICE_SERVER_PING'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\VoiceServerPing');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['RECONNECT'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Reconnect');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['REQUEST_GUILD_MEMBERS'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\RequestGuildMembers');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['INVALIDATE_SESSION'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\InvalidateSession');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['HELLO'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Hello');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['HEARTBEAT_ACK'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\HeartbeatAck');
        $this->register(\CharlotteDunois\Yasmin\Constants::$opcodes['GUILD_SYNC'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\GuildSync');
    }
    
    function client() {
        return $this->wsmanager->client();
    }
    
    function wsmanager() {
        return $this->wsmanager;
    }
    
    function getSequence() {
        return $this->sequence;
    }
    
    function getHandler($name) {
        if(isset($this->handlers[$name])) {
            return $this->handlers[$name];
        }
        
        throw new \Exception('Can not find handler');
    }
    
    function handle($message) {
        try {
            $packet = json_decode($message->getPayload(), true);
            $this->wsmanager->client()->emit('raw', $packet);
            
            if($packet['s']) {
                $this->sequence = $packet['s'];
            }
            
            if(isset($this->handlers[$packet['op']])) {
                $this->handlers[$packet['op']]->handle($packet);
            }
        } catch(\Exception $e) {
            var_dump($e->getMessage());
            /* Continue regardless of error */
        }
    }
    
    private function register($op, $class) {
        $this->handlers[$op] = new $class($this);
    }
}
