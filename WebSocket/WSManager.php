<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket;

class WSManager extends \League\Event\Emitter {
    public $client;
    public $ratelimits = array(
        'total' => 120,
        'time' => 60,
        'remaining' => 120,
        'timer' => NULL,
        'dateline' => 0
    );
    public $wsHeartbeat = array(
        'ack' => true,
        'dateline' => 0
    );
    
    private $gateway;
    private $wsSessionID;
    private $wshandler;
    private $ws;
    
    function __construct($client) {
        $this->client = $client;
        $this->wshandler = new \CharlotteDunois\Yasmin\WebSocket\WSHandler($this);
    }
    
    function client() {
        return $this->client;
    }
    
    function getWSHandler() {
        return $this->wshandler;
    }
    
    function status() {
        if(!$this->ws) {
            return -1;
        }
        
        
    }
    
    function connect($gateway = null) {
        if(!$gateway && !$this->gateway) {
            throw new \Exception('Can not connect to unknown gateway');
        }
        
        if($this->gateway) {
            $this->client->emit('reconnect');
        }
        
        $this->gateway = $gateway;
        
        $connector = new \Ratchet\Client\Connector($this->client->getLoop());
        $this->client->emit('debug', 'Connecting to WS '.$gateway);
        
        return $connector($gateway)->done(function (\Ratchet\Client\WebSocket $conn) {
            $this->ws = &$conn;
            
            $this->emit('open');
            $this->client->emit('debug', 'Connected to WS');
            
            $ratelimits = &$this->ratelimits;
            $ratelimits['timer'] = $this->client->getLoop()->addPeriodicTimer(60, function () use($ratelimits) {
                $ratelimits['timer']['remaining'] = $ratelimits['timer']['total'];
            });
            
            if($this->wsSessionID === NULL) {
                $this->client->emit('debug', 'Sending IDENTIFY packet to WS');
                $this->sendIdentify('IDENTIFY');
            } else {
                $this->client->emit('debug', 'Sending RESUME "'.$this->wsSessionID.'" packet to WS');
                $this->sendIdentify('RESUME', $this->wsSessionID);
            }
            
            $this->ws->on('message', function ($message) {
                $this->wshandler->handle($message);
            });
            
            $this->ws->on('error', function ($error) {
                if(!$this->client->readyTimestamp) {
                    throw $error;
                }
                
                $this->client->emit('error', $error);
            });
            
            $this->ws->on('close', function ($code) {
                if($this->ratelimits['timer']) {
                    $this->client->getLoop()->cancelTimer($this->ratelimits['timer']);
                }
                
                $this->emit('close', $code);
                $this->client->emit('disconnect', $code);
                
                if($event !== 1000) {
                    $this->connect($this->gateway);
                }
            });
            
            return \React\Promise\resolve();
        });
    }
    
    function disconnect() {
        $this->client->emit('debug', 'Disconnecting from WS');
        
        $this->wsSessionID = NULL;
        $this->ws->close(1000);
    }
    
    function send(array $packet) {
        return $this->_send($packet);
    }
    
    function setSessionID(string $id) {
        $this->wsSessionID = $id;
    }
    
    function sendIdentify(string $opname, $sessionid = NULL) {
        $packet = array(
            'op' => \CharlotteDunois\Yasmin\Constants::$opcodes[$opname],
            'd' => array(
                'token' => $this->client->token,
                'properties' => array(
                    '$os' => php_uname('s'),
                    '$browser' => 'NekoCord',
                    '$device' => 'NekoCord'
                ),
                'compress' => (bool) $this->client->getOption('compress', false),
                'large_threshold' => (int) $this->client->getOption('largeThreshold', 250),
                'shard' => array(
                    (int) $this->client->getOption('shardID', 0),
                    (int) $this->client->getOption('shardCount', 1)
                ),
                'presence' => array('status' => 'online')
            )
        );
        
        if(is_string($sessionid)) {
            $packet['d']['session_id'] = $sessionid;
        }
        
        $this->_send($packet);
    }
    
    function heartbeat() {
        if($this->wsHeartbeat['ack'] === false) {
            return $this->heartFailure();
        }
        
        $this->client->emit('debug', 'Sending heartbeat');
        
        $this->wsHeartbeat['ack'] = false;
        $this->wsHeartbeat['dateline'] = microtime(true);
        
        $this->_send(array(
            'op' => \CharlotteDunois\Yasmin\Constants::$opcodes['HEARTBEAT'],
            'd' => $this->wshandler->getSequence()
        ));
    }
    
    function heartbeatAck() {
        $this->client->emit('debug', 'Sending heartbeat ack');
        $this->_send(array(
            'op' => \CharlotteDunois\Yasmin\Constants::$opcodes['HEARTBEAT_ACK'],
            'd' => null
        ));
    }
    
    function heartFailure() {
        $this->client->emit('debug', 'WS heart failure');
        
        $this->ws->close(1006, 'No heartbeat ack received');
        $this->connect($this->gateway);
    }
    
    function _send(array $packet) {
        $this->client->emit('debug', 'Sending packet with OP code '.$packet['op']);
        return $this->ws->send(json_encode($packet));
    }
    
    function on(...$args) {
        return $this->addListener(...$args);
    }
    
    function once(...$args) {
        return $this->addOneTimeListener(...$args);
    }
    
    function emit($name, ...$args) {
        $event = new \CharlotteDunois\Yasmin\Event($name, ...$args);
        $event->setEmitter($this);
        return parent::emit($event);
    }
}
