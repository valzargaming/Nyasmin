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
        'dateline' => 0,
        'queue' => array(),
        'running' => false
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
        
        return 1;
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
            $ratelimits['timer'] = $this->client->getLoop()->addPeriodicTimer($ratelimits['time'], function () use($ratelimits) {
                $ratelimits['remaining'] = $ratelimits['total'];
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
            
            $this->ws->on('close', function ($code, $reason) {
                if($this->ratelimits['timer']) {
                    $this->client->getLoop()->cancelTimer($this->ratelimits['timer']);
                }
                
                $this->ratelimits['queue'] = array();
                $this->ws = NULL;
                
                $this->emit('close', $code, $reason);
                $this->client->emit('disconnect', $code, $reason);
                
                if($code !== 1000) {
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
        $this->ws = null;
    }
    
    function send(array $packet) {
        if($this->status() < 1) {
            $this->client->emit('debug', 'Tried sending a WS message before a connection was made');
            return false;
        }
        
        $this->ratelimits['queue'][] = $packet;
        
        if($this->ratelimits['running'] === false) {
            $this->client->getLoop()->addTimer(0.001, function () {
                $this->processQueue();
            });
        }
        
        return true;
    }
    
    function processQueue() {
         if($this->ratelimits['remaining'] === 0) {
             return;
         } elseif(count($this->ratelimits['queue']) === 0) {
             return;
         }
         
         $this->ratelimits['running'] = true;
         
         while($this->ratelimits['remaining'] > 0 && count($this->ratelimits['queue']) > 0) {
             $packet = array_shift($this->ratelimits['queue']);
             $this->ratelimits['remaining']--;
             
             $this->_send($packet);
         }
         
         $this->ratelimits['running'] = false;
    }
    
    function setSessionID(string $id) {
        $this->wsSessionID = $id;
    }
    
    function sendIdentify(string $opname, $sessionid = NULL) {
        if(empty($this->client->token)) {
            $this->client->emit('Debug', 'No client token to start with');
            return;
        }
        
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
        
        $this->send($packet);
    }
    
    function heartbeat() {
        if($this->wsHeartbeat['ack'] === false) {
            return $this->heartFailure();
        }
        
        $this->client->emit('debug', 'Sending heartbeat');
        
        $this->wsHeartbeat['ack'] = false;
        $this->wsHeartbeat['dateline'] = microtime(true);
        
        $this->send(array(
            'op' => \CharlotteDunois\Yasmin\Constants::$opcodes['HEARTBEAT'],
            'd' => $this->wshandler->getSequence()
        ));
    }
    
    function heartbeatAck() {
        $this->client->emit('debug', 'Sending heartbeat ack');
        $this->send(array(
            'op' => \CharlotteDunois\Yasmin\Constants::$opcodes['HEARTBEAT_ACK'],
            'd' => null
        ));
    }
    
    function heartFailure() {
        $this->client->emit('debug', 'WS heart failure');
        
        $this->ws->close(1006, 'No heartbeat ack received');
        $this->ws = null;
        
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
