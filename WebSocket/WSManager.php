<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord\WebSocket;

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
        $this->wshandler = new \CharlotteDunois\NekoCord\WebSocket\WSHandler($this);
    }
    
    function client() {
        return $this->client;
    }
    
    function getWSHandler() {
        return $this->wshandler;
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
        return $connector($gateway)->done(function (\Ratchet\Client\WebSocket $conn) {
            $this->ws = &$conn;
            
            $this->emit('open');
            
            $ratelimits = &$this->ratelimits;
            $ratelimits['timer'] = $this->client->getLoop()->addPeriodicTimer(60, function () use($ratelimits) {
                $ratelimits['timer']['remaining'] = $ratelimits['timer']['total'];
            });
            
            if($this->wsSessionID === NULL) {
                $this->sendIdentify('IDENTIFY');
            } else {
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
            
            $this->ws->on('close', function ($event) {
                var_dump($event);
                
                if($this->ratelimits['timer']) {
                    $this->client->getLoop()->cancelTimer($this->ratelimits['timer']);
                }
                
                $this->emit('close');
                $this->client->emit('disconnect');
                
                
            });
            
            return \React\Promise\resolve();
        });
    }
    
    function disconnect() {
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
            'op' => \CharlotteDunois\NekoCord\Constants::$opcodes[$opname],
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
        
        echo 'Sending heartbeat'.PHP_EOL;
        
        $this->wsHeartbeat['ack'] = false;
        $this->wsHeartbeat['dateline'] = microtime(true);
        
        $this->_send(array(
            'op' => \CharlotteDunois\NekoCord\Constants::$opcodes['HEARTBEAT'],
            'd' => $this->wshandler->getSequence()
        ));
    }
    
    function heartbeatAck() {
        echo 'Heartbeat ACK'.PHP_EOL;
        $this->_send(array(
            'op' => \CharlotteDunois\NekoCord\Constants::$opcodes['HEARTBEAT_ACK'],
            'd' => null
        ));
    }
    
    function heartFailure() {
        $this->ws->close(1006, 'No heartbeat ack received');
        $this->connect($this->gateway);
    }
    
    function _send(array $packet) {
        var_dump($packet);
        return $this->ws->send(json_encode($packet));
    }
    
    function on(...$args) {
        return $this->addListener(...$args);
    }
    
    function once(...$args) {
        return $this->addOneTimeListener(...$args);
    }
    
    function emit($name, ...$args) {
        $event = new \CharlotteDunois\NekoCord\Event($name, ...$args);
        $event->setEmitter($this);
        return parent::emit($event);
    }
}
