<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket;

/**
 * Handles the WS connection.
 * @access private
 */
class WSManager extends \CharlotteDunois\Yasmin\EventEmitter {
    /**
     * @var \CharlotteDunois\Yasmin\Client
     */
    protected $client;
    
    /**
     * @var \Ratchet\Client\Connector
     */
    protected $connector;
    
    /**
     * @var \CharlotteDunois\Yasmin\WebSocket\WSHandler
     */
    protected $wshandler;
    
    /**
     * @var \Ratchet\Client\WebSocket
     */
    protected $ws;
    
    /**
     * @var resource
     */
    protected $compressContext;
    
    /**
     * The WS ratelimits.
     * @var array
     */
    public $ratelimits = array(
        'total' => 120,
        'time' => 60,
        'remaining' => 120,
        'timer' => null,
        'dateline' => 0
    );
    
    /**
     * The WS heartbeat.
     * @var array
     */
    public $wsHeartbeat = array(
        'ack' => true,
        'dateline' => 0
    );
    
    /**
     * The WS queue.
     * @var array
     */
    protected $queue = array();
    
    /**
     * The WS queue processing status.
     * @var bool
     */
    protected $running = false;
    
    /**
     * If the connection got closed, did we expect it?
     * @var bool
     */
    protected $expectedClose = false;
    
    /**
     * The WS gateway address.
     * @var string
     */
    protected $gateway;
    
    /**
     * The WS connection status
     * @var int
     */
    protected $wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_DISCONNECTED;
    
    /**
     * The Discord Session ID.
     * @var string|null
     */
    protected $wsSessionID;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        $this->wshandler = new \CharlotteDunois\Yasmin\WebSocket\WSHandler($this);
        
        $compression = $this->client->getOption('ws.compression');
        if($compression === true || $compression === null) {
            $compression = \CharlotteDunois\Yasmin\Constants::WS_DEFAULT_COMPRESSION;
        }
        
        switch($compression) {
            default:
                $name = \str_replace('-', '', \ucwords($compression, '-'));
                if(strpos($name, '\\') === false) {
                    $name = '\\CharlotteDunois\\Yasmin\\WebSocket\\Compression\\'.$name;
                }
                
                $name::supported();
                
                $interfaces = \class_implements($name);
                if(!in_array('CharlotteDunois\\Yasmin\\WebSocket\\Compression\\CompressionInterface', $interfaces)) {
                    throw new \Exception('Specified WS compression class does not implement necessary interface');
                }
                
                $this->compressContext = new $name();
            break;
            case false:
                /* Nothing to do */
            break;
        }
    }
    
    /**
     * @property-read \CharlotteDunois\Yasmin\Client               $client
     * @property-read int                                          $status
     * @property-read \CharlotteDunois\Yasmin\WebSocket\WSHandler  $wshandler
     */
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
            case 'status':
                return $this->wsStatus;
            break;
            case 'wshandler':
                return $this->wshandler;
            break;
        }
        
        return null;
    }
    
    function connect($gateway, array $querystring = array()) {
        if($this->ws) {
            return \React\Promise\resolve();
        }
        
        if(!$gateway && !$this->gateway) {
            throw new \Exception('Can not connect to unknown gateway');
        }
        
        if($this->compressContext) {
            $querystring['compress'] = $this->compressContext->getName();
        }
        
        $reconnect = false;
        if($this->gateway) {
            $this->client->emit('reconnect');
            $reconnect = true;
        } elseif(!empty($querystring)) {
            $gateway = \rtrim($gateway, '/').'/?'.\http_build_query($querystring);
        }
        
        $this->gateway = $gateway;
        $this->expectedClose = false;
        
        $this->client->emit('debug', 'Connecting to WS '.$gateway);
        
        $connector = new \Ratchet\Client\Connector($this->client->getLoop());
        
        if($this->wsStatus < \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTING || $this->wsStatus > \CharlotteDunois\Yasmin\Constants::WS_STATUS_RECONNECTING) {
            $this->wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTING;
        }
        
        return $connector($gateway)->then(function (\Ratchet\Client\WebSocket $conn) {
            $this->ws = &$conn;
            
            if($this->compressContext) {
                $this->compressContext->init();
            }
            
            $this->wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_NEARLY;
            
            $this->on('ready', function () {
                $this->wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTED;
            });
            
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
                $message = $message->getPayload();
                if(!$message) {
                    return;
                }
                
                if($this->compressContext) {
                    try {
                        $message = $this->compressContext->decompress($message);
                    } catch(\InvalidArgumentException $e) {
                        return;
                    } catch(\BadMethodCallException $e) {
                        $this->client->emit('error', $e);
                        return;
                    }
                }
                
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
                $this->queue = array();
                $this->wsHeartbeat['ack'] = true;
                
                if($this->compressContext) {
                    $this->compressContext->destroy();
                }
                
                $this->ws = null;
                
                if($this->wsStatus <= \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTED) {
                    $this->wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_DISCONNECTED;
                }
                
                $this->emit('close', $code, $reason);
                $this->client->emit('disconnect', $code, $reason);
                
                if($this->expectedClose === true) {
                    return;
                }
                
                if($code === 1000 || $code >= 4000) {
                    $this->wsSessionID = null;
                }
                
                $this->wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_RECONNECTING;
                $this->connect($this->gateway);
            });
        }, function($error) {
            if($reconnect) {
                return $this->client->login($this->client->token, true);
            }
            
            throw $error;
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
        
        $this->wsStatus = \CharlotteDunois\Yasmin\Constants::WS_STATUS_IDLE;
        $this->wsSessionID = null;
    }
    
    function send(array $packet) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($packet) {
            if($this->wsStatus !== \CharlotteDunois\Yasmin\Constants::WS_STATUS_NEARLY && $this->wsStatus !== \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTED) {
                return $reject(new \Exception('Can not send WS message before a WS connection is established'));
            }
            
            $this->queue[] = array('packet' => $packet, 'resolve' => $resolve, 'reject' => $reject);
            
            if($this->running === false) {
                $this->processQueue();
            }
        }));
    }
    
    function processQueue() {
         if($this->running === true) {
             return;
         } elseif($this->ratelimits['remaining'] === 0) {
             return;
         } elseif(\count($this->queue) === 0) {
             return;
         }
         
         $this->running = true;
         
         while($this->ratelimits['remaining'] > 0 && \count($this->queue) > 0) {
             $element = \array_shift($this->queue);
             $this->ratelimits['remaining']--;
             
             if(!$this->ws) {
                 $element['reject']();
                 break;
             }
             
             $this->_send($element['packet']);
             $element['resolve']();
         }
         
         $this->running = false;
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
                'compress' => false,
                'large_threshold' => (int) $this->client->getOption('ws.largeThreshold', 250),
                'shard' => array(
                    (int) $this->client->getOption('shardID', 0),
                    (int) $this->client->getOption('shardCount', 1)
                )
            )
        );
        
        $presence = (array) $this->client->getOption('ws.presence', array());
        if(\is_array($presence) && !empty($presence)) {
            $packet['d']['presence'] = $presence;
        }
        
        if($packet['op'] === \CharlotteDunois\Yasmin\Constants::OPCODES['RESUME']) {
            $packet['d']['session_id'] = $sessionid;
            $packet['d']['seq'] = $this->wshandler->sequence;
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
        
        try {
            $this->connect($this->gateway)->done();
        } catch(\Exception $e) {
            $this->client->login($this->client->token, true);
        }
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
}
