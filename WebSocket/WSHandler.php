<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket;

class WSHandler {
    private $handlers = array();
    private $sequence = NULL;
    private $wsmanager;
    
    function __construct($wsmanager) {
        $this->wsmanager = $wsmanager;
        
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['DISPATCH'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\Dispatch');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['HEARTBEAT'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\Heartbeat');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['PRESENCE'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\Presence');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['VOICE_STATE_UPDATE'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\VoiceStateUpdate');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['VOICE_SERVER_PING'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\VoiceServerPing');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['RECONNECT'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\Reconnect');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['REQUEST_GUILD_MEMBERS'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\RequestGuildMembers');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['INVALIDATE_SESSION'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\InvalidateSession');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['HELLO'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\Hello');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['HEARTBEAT_ACK'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\HeartbeatAck');
        $this->register(\CharlotteDunois\NekoCord\Constants::$opcodes['GUILD_SYNC'], '\CharlotteDunois\NekoCord\WebSocket\Handlers\GuildSync');
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
