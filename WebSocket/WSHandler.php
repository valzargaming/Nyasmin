<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket;

/**
 * Handles WS messages.
 * @access private
 */
class WSHandler {
    private $handlers = array();
    private $sequence = null;
    private $wsmanager;
    
    function __construct(\CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->wsmanager = $wsmanager;
        
        $this->register(\CharlotteDunois\Yasmin\Constants::OPCODES['DISPATCH'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Dispatch');
        $this->register(\CharlotteDunois\Yasmin\Constants::OPCODES['HEARTBEAT'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Heartbeat');
        $this->register(\CharlotteDunois\Yasmin\Constants::OPCODES['RECONNECT'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Reconnect');
        $this->register(\CharlotteDunois\Yasmin\Constants::OPCODES['INVALIDATE_SESSION'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\InvalidateSession');
        $this->register(\CharlotteDunois\Yasmin\Constants::OPCODES['HELLO'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\Hello');
        $this->register(\CharlotteDunois\Yasmin\Constants::OPCODES['HEARTBEAT_ACK'], '\CharlotteDunois\Yasmin\WebSocket\Handlers\HeartbeatAck');
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->wsmanager->client;
            break;
            case 'sequence':
                return $this->sequence;
            break;
            case 'wsmanager':
                return $this->wsmanager;
            break;
        }
        
        return null;
    }
    
    function getHandler($name) {
        if(isset($this->handlers[$name])) {
            return $this->handlers[$name];
        }
        
        throw new \Exception('Can not find handler');
    }
    
    function handle($message) {
        try {
            $packet = \json_decode($message->getPayload(), true);
            $this->client->emit('raw', $packet);
            
            if($packet['s']) {
                $this->sequence = $packet['s'];
            }
            
            if(isset($this->handlers[$packet['op']])) {
                $this->handlers[$packet['op']]->handle($packet);
            }
        } catch(\Throwable $e) {
            $this->wsmanager->client->emit('error', $e);
        } catch(\Exception $e) {
            $this->wsmanager->client->emit('error', $e);
        } catch(\Error $e) {
            $this->wsmanager->client->emit('error', $e);
        }
    }
    
    private function register($op, $class) {
        $this->handlers[$op] = new $class($this);
    }
}
