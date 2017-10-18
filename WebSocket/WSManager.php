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
 * Handles the WS connection.
 * @access private
 */
class WSManager extends \CharlotteDunois\Yasmin\EventEmitter {
    public $ratelimits = array(
        'total' => 120,
        'time' => 60,
        'remaining' => 120,
        'timer' => null,
        'dateline' => 0,
        'queue' => array(),
        'running' => false
    );
    public $wsHeartbeat = array(
        'ack' => true,
        'dateline' => 0
    );
    
    private $expectedClose = false;
    private $gateway;
    private $wsSessionID;
    
    private $client;
    private $wshandler;
    private $ws;
    
    function __construct($client) {
        $this->client = $client;
        $this->wshandler = new \CharlotteDunois\Yasmin\WebSocket\WSHandler($this);
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
            case 'wshandler':
                return $this->wshandler;
            break;
        }
        
        return null;
    }
    
    function status() {
        if(!$this->ws) {
            return -1;
        }
        
        return 1;
    }
    
    function connect($gateway = null) {
        if($this->ws) {
            return \React\Promise\resolve();
        }
        
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
            
            if(empty($this->wsSessionID)) {
                $this->client->emit('debug', 'Sending IDENTIFY packet to WS');
                $this->sendIdentify();
            } else {
                $this->client->emit('debug', 'Sending RESUME "'.$this->wsSessionID.'" packet to WS');
                $this->sendIdentify($this->wsSessionID);
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
                
                $this->ratelimits['remaining'] = $this->ratelimits['total'];
                $this->ratelimits['timer'] = null;
                $this->ratelimits['queue'] = array();
                $this->wsHeartbeat['ack'] = true;
                
                $this->ws = null;
                $this->emit('close', $code, $reason);
                
                if($this->expectedClose === true) {
                    return;
                }
                
                $this->client->emit('disconnect', $code, $reason);
                
                if($code !== 1000) {
                    if($code >= 4000) {
                        $this->wsSessionID = null;
                    }
                    
                    $this->connect($this->gateway);
                }
            });
            
            return \React\Promise\resolve();
        });
    }
    
    function disconnect() {
        if(!$this->ws) {
            return;
        }
        
        $this->processQueue();
        
        $this->client->emit('debug', 'Disconnecting from WS');
        
        $this->expectedClose = true;
        $this->ws->close(1000);
        $this->wsSessionID = null;
    }
    
    function send(array $packet) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($packet) {
            if($this->status() < 1) {
                $this->client->emit('debug', 'Tried sending a WS message before a connection was made');
                return false;
            }
            
            $this->ratelimits['queue'][] = array('packet' => $packet, 'resolve' => $resolve, 'reject' => $reject);
            
            if($this->ratelimits['running'] === false) {
                $this->processQueue();
            }
        });
    }
    
    function processQueue() {
         if($this->ratelimits['running'] === true) {
             return;
         } elseif($this->ratelimits['remaining'] === 0) {
             return;
         } elseif(\count($this->ratelimits['queue']) === 0) {
             return;
         }
         
         $this->ratelimits['running'] = true;
         
         while($this->ratelimits['remaining'] > 0 && \count($this->ratelimits['queue']) > 0) {
             $element = \array_shift($this->ratelimits['queue']);
             $this->ratelimits['remaining']--;
             
             if(!$this->ws) {
                 $element['reject']();
                 break;
             }
             
             $this->_send($element['packet']);
             $element['resolve']();
         }
         
         $this->ratelimits['running'] = false;
    }
    
    function setSessionID(string $id) {
        $this->wsSessionID = $id;
    }
    
    function sendIdentify($sessionid = null) {
        if(empty($this->client->token)) {
            $this->client->emit('Debug', 'No client token to start with');
            return;
        }
        
        $packet = array(
            'op' => (!empty($sessionid) && \is_string($sessionid) ? \CharlotteDunois\Yasmin\Constants::OPCODES['RESUME'] : \CharlotteDunois\Yasmin\Constants::OPCODES['IDENTIFY']),
            'd' => array(
                'token' => $this->client->token,
                'properties' => array(
                    '$os' => \php_uname('s'),
                    '$browser' => 'Yasmin',
                    '$device' => 'Yasmin'
                ),
                'compress' => (bool) $this->client->getOption('compress', false),
                'large_threshold' => (int) $this->client->getOption('largeThreshold', 250),
                'shard' => array(
                    (int) $this->client->getOption('shardID', 0),
                    (int) $this->client->getOption('shardCount', 1)
                )
            )
        );
        
        $presence = $this->client->getOption('connectPresence');
        if(\is_array($presence)) {
            $packet['d']['presence'] = $presence;
        }
        
        if($packet['op'] === \CharlotteDunois\Yasmin\Constants::OPCODES['RESUME']) {
            $packet['d']['session_id'] = $sessionid;
        }
        
        $this->send($packet);
    }
    
    function heartbeat() {
        if($this->wsHeartbeat['ack'] === false) {
            return $this->heartFailure();
        }
        
        $this->client->emit('debug', 'Sending WS heartbeat');
        
        $this->wsHeartbeat['ack'] = false;
        $this->wsHeartbeat['dateline'] = microtime(true);
        
        $this->send(array(
            'op' => \CharlotteDunois\Yasmin\Constants::OPCODES['HEARTBEAT'],
            'd' => $this->wshandler->sequence
        ));
    }
    
    function heartbeatAck() {
        $this->client->emit('debug', 'Sending WS heartbeat ack');
        $this->send(array(
            'op' => \CharlotteDunois\Yasmin\Constants::OPCODES['HEARTBEAT_ACK'],
            'd' => null
        ));
    }
    
    function heartFailure() {
        $this->client->emit('debug', 'WS heart failure');
        
        $this->ws->close(1006, 'No heartbeat ack received');
        $this->ws = null;
        
        $this->connect($this->gateway);
    }
    
    function _pong($end) {
        $time = \ceil(($end - $this->wsHeartbeat['dateline']) * 1000);
        $this->client->pings[] = $time;
        
        if(\count($this->client->pings) > 3) {
            $this->client->pings = \array_slice($this->client->pings, 0, 3);
        }
        
        $this->wsHeartbeat['ack'] = true;
        $this->wsHeartbeat['dateline'] = 0;
    }
    
    function _send(array $packet) {
        if(!$this->ws) {
            $this->client->emit('debug', 'Tried sending a WS packet with no WS connection');
            return;
        }
        
        $this->client->emit('debug', 'Sending WS packet with OP code '.$packet['op']);
        return $this->ws->send(json_encode($packet));
    }
    
    function emit($name, ...$args) {
        if($name === 'debug' && $this->client->getOption('disableDebugEvent', false) === true) {
            return;
        }
        
        return parent::emit($name, ...$args);
    }
}
