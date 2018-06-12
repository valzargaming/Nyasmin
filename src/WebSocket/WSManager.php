<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket;

/**
 * Handles the WS connection.
 *
 * @property \CharlotteDunois\Yasmin\Client                          $client
 * @property \CharlotteDunois\Yasmin\Interfaces\WSEncodingInterface  $encoding
 * @property int                                                     $status
 * @property \CharlotteDunois\Yasmin\WebSocket\WSHandler             $wshandler
 *
 * @internal
 */
class WSManager implements \CharlotteDunois\Events\EventEmitterInterface {
    use \CharlotteDunois\Events\EventEmitterTrait;
    
    /**
     * WS OP codes.
     * @var array
     * @internal
     */
    const OPCODES = array(
        'DISPATCH' => 0,
        'HEARTBEAT' => 1,
        'IDENTIFY' => 2,
        'STATUS_UPDATE' => 3,
        'VOICE_STATE_UPDATE' => 4,
        'RESUME' => 6,
        'RECONNECT' => 7,
        'REQUEST_GUILD_MEMBERS' => 8,
        'INVALID_SESSION' => 9,
        'HELLO' => 10,
        'HEARTBEAT_ACK' => 11,
        
        0 => 'DISPATCH',
        1 => 'HEARTBEAT',
        2 => 'IDENTIFY',
        3 => 'STATUS_UPDATE',
        4 => 'VOICE_STATE_UPDATE',
        6 => 'RESUME',
        7 => 'RECONNECT',
        8 => 'REQUEST_GUILD_MEMBERS',
        9 => 'INVALID_SESSION',
        10 => 'HELLO',
        11 => 'HEARTBEAT_ACK'
    );
    
    /**
     * WS constants. Query string parameters.
     * @var array
     * @internal
     */
    const WS = array(
        'v' => 6,
        'encoding' => 'json'
    );
    
    /**
     * WS Close codes.
     * @var array
     * @internal
     */
    const WS_CLOSE_CODES = array(
        4004 => 'Tried to identify with an invalid token',
        4010 => 'Sharding data provided was invalid',
        4011 => 'Shard would be on too many guilds if connected',
        4012 => 'Invalid gateway version'
    );
    
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
     * @var \CharlotteDunois\Yasmin\Interfaces\WSCompressionInterface
     */
    protected $compressContext;
    
    /**
     * @var \CharlotteDunois\Yasmin\Interfaces\WSEncodingInterface
     */
    protected $encoding;
    
    /**
     * The WS ratelimits.
     * @var array
     */
    public $ratelimits = array(
        'total' => 120,
        'time' => 60,
        'remaining' => 120,
        'timer' => null,
        'dateline' => 0,
        'heartbeatRoom' => 2
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
     * The WS authentication state.
     * @var bool
     */
    protected $authenticated = false;
    
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
     * If the connection gets closed, did we expect it?
     * @var bool
     */
    protected $expectedClose = false;
    
    /**
     * The WS gateway address.
     * @var string
     */
    protected $gateway;
    
    /**
     * The timestamp of the latest identify (Ratelimit 1/5s).
     * @var int
     */
    protected $lastIdentify;
    
    /**
     * Whether we should use the previous sequence for RESUME (for after compress context failure).
     * @var bool
     */
    protected $previous = false;
    
    
    /**
     * WS close codes, sorted by resumable session and ends everything.
     * @var array
     */
    protected $wsCloseCodes = array(
        'end' => array(
            4004, 4010, 4011, 4012
        ),
        'resumable' => array(
            4001, 4002, 4003, 4005, 4008
        )
    );
    
    /**
     * The WS connection status
     * @var int
     */
    protected $wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_DISCONNECTED;
    
    /**
     * The Discord Session ID.
     * @var string|null
     */
    protected $wsSessionID;
    
    /**
     * DO NOT initialize this class yourself.
     * @param \CharlotteDunois\Yasmin\Client  $client
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        $this->wshandler = new \CharlotteDunois\Yasmin\WebSocket\WSHandler($this);
        
        $compression = $this->client->getOption('ws.compression', \CharlotteDunois\Yasmin\Client::WS_DEFAULT_COMPRESSION);
        
        $name = \str_replace('-', '', \ucwords($compression, '-'));
        if(strpos($name, '\\') === false) {
            $name = '\\CharlotteDunois\\Yasmin\\WebSocket\\Compression\\'.$name;
        }
        
        if(!\class_exists($name, true)) {
            throw new \Exception('Specified WS compression class does not exist');
        }
        
        $name::supported();
        
        $interfaces = \class_implements($name);
        if(!\in_array('CharlotteDunois\\Yasmin\\Interfaces\\WSCompressionInterface', $interfaces)) {
            throw new \Exception('Specified WS compression class does not implement necessary interface');
        }
        
        $this->compressContext = new $name();
        
        $this->on('ready', function () {
            $this->wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_CONNECTED;
        });
    }
    
    /**
     * @throws \Exception
     * @internal
     */
    function __isset($name) {
        try {
            return $this->$name !== null;
        } catch (\RuntimeException $e) {
            if($e->getTrace()[0]['function'] === '__get') {
                return false;
            }
            
            throw $e;
        }
    }
    
    /**
     * @throws \RuntimeException
     */
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
            case 'encoding':
                return $this->encoding;
            break;
            case 'status':
                return $this->wsStatus;
            break;
            case 'wshandler':
                return $this->wshandler;
            break;
        }
        
        throw new \RuntimeException('Undefined property: '.\get_class($this).'::$'.$name);
    }
    
    /**
     * Disconnects.
     * @return void
     */
    function destroy() {
        $this->disconnect();
    }
    
    /**
     * Connects to the gateway url.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \Exception
     */
    function connect(?string $gateway = null, array $querystring = array()) {
        if($this->ws) {
            return \React\Promise\resolve();
        }
        
        if(!$gateway && !$this->gateway) {
            throw new \Exception('Unable to connect to unknown gateway');
        }
        
        if(($this->lastIdentify ?? 0) > (\time() - 5)) {
            return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($gateway, $querystring) {
                $this->client->getLoop()->addTimer((5 - (\time() - $this->lastIdentify)), function () use ($gateway, $querystring, $resolve, $reject) {
                    $this->connect($gateway, $querystring)->done($resolve, $reject);
                });
            }));
        }
        
        if($this->encoding === null) {
            $encoding = $querystring['encoding'] ?? $this->encoding ?? self::WS['encoding'];
            
            $name = \str_replace('-', '', \ucwords($encoding, '-'));
            if(strpos($name, '\\') === false) {
                $name = '\\CharlotteDunois\\Yasmin\\WebSocket\\Encoding\\'.$name;
            }
            
            $name::supported();
            
            $interfaces = \class_implements($name);
            if(!in_array('CharlotteDunois\\Yasmin\\Interfaces\\WSEncodingInterface', $interfaces)) {
                throw new \Exception('Specified WS encoding class does not implement necessary interface');
            }
            
            $this->encoding = new $name();
            $querystring['encoding'] = $this->encoding->getName();
        }
        
        if($this->compressContext && $this->compressContext->getName()) {
            $this->client->emit('debug', 'Using compress context '.$this->compressContext->getName());
            $querystring['compress'] = $this->compressContext->getName();
        }
        
        if($this->gateway && (!$gateway || $this->gateway === $gateway)) {
            if(!$gateway) {
                $gateway = $this->gateway;
            }
            
            if(($this->lastIdentify ?? 0) > (\time() - 30)) { // Make sure we reconnect after at least 30 seconds, if there was like an outage, to prevent spamming
                return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($gateway, $querystring) {
                    $time = (30 - (\time() - $this->lastIdentify));
                    $this->client->emit('debug', 'Reconnect will be attempted in '.$time.' seconds');
                    
                    $this->client->getLoop()->addTimer($time, function () use ($gateway, $querystring, $resolve, $reject) {
                        $this->connect($gateway, $querystring)->done($resolve, $reject);
                    });
                }));
            }
            
            $this->client->emit('reconnect');
        } elseif(!empty($querystring)) {
            $gateway = \rtrim($gateway, '/').'/?'.\http_build_query($querystring);
        }
        
        $this->gateway = $gateway;
        $this->expectedClose = false;
        
        $this->client->emit('debug', 'Connecting to WS '.$gateway);
        
        if(!$this->connector) {
            $this->connector = new \Ratchet\Client\Connector($this->client->getLoop());
        }
        
        if($this->wsStatus < \CharlotteDunois\Yasmin\Client::WS_STATUS_CONNECTING || $this->wsStatus > \CharlotteDunois\Yasmin\Client::WS_STATUS_RECONNECTING) {
            $this->wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_CONNECTING;
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($gateway) {
            $connector = $this->connector;
            $connector($gateway)->done(function (\Ratchet\Client\WebSocket $conn) use ($resolve, $reject) {
                $this->ws = &$conn;
                
                if($this->compressContext) {
                    $this->compressContext->init();
                    $this->client->emit('debug', 'Initialized compress context');
                }
                
                $this->wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_NEARLY;
                
                $this->emit('open');
                $this->client->emit('debug', 'Connected to WS');
                
                $ratelimits = &$this->ratelimits;
                $ratelimits['timer'] = $this->client->getLoop()->addPeriodicTimer($ratelimits['time'], function () use($ratelimits) {
                    $ratelimits['remaining'] = $ratelimits['total'] - $ratelimits['heartbeatRoom']; // Let room in WS ratelimit for X heartbeats per X seconds.
                });
                
                $this->lastIdentify = \time();
                $this->sendIdentify();
                $ready = false;
                
                $this->once('self.ws.ready', function () use (&$ready, $resolve) {
                    $ready = true;
                    $resolve();
                });
                
                $this->once('self.ws.error', function ($error) use (&$ready, $reject) {
                    if(!$ready) {
                        $this->disconnect();
                        $reject(new \Exception($error));
                    }
                });
                
                $this->ws->on('message', function ($message) {
                    $message = $message->getPayload();
                    if(!$message) {
                        return;
                    }
                    
                    if($this->compressContext) {
                        try {
                            $message = $this->compressContext->decompress($message);
                            
                            if($this->previous) {
                                $this->previous = false;
                            }
                        } catch (\Throwable | \Exception | \Error $e) {
                            $this->previous = !$this->previous;
                            $this->client->emit('error', $e);
                            $this->reconnect(true);
                            return;
                        }
                    }
                    
                    $this->wshandler->handle($message);
                });
                
                $this->ws->on('error', function ($error) use (&$ready, $reject) {
                    if($ready === false) {
                        return $reject($error);
                    }
                    
                    $this->client->emit('error', $error);
                });
                
                $this->ws->on('close', function (int $code, string $reason) use ($reject) {
                    if($this->ratelimits['timer']) {
                        $this->client->getLoop()->cancelTimer($this->ratelimits['timer']);
                    }
                    
                    $this->ratelimits['remaining'] = $this->ratelimits['total'] - $this->ratelimits['heartbeatRoom'];
                    $this->ratelimits['timer'] = null;
                    
                    $this->authenticated = false;
                    $this->wsHeartbeat['ack'] = true;
                    
                    if($this->compressContext) {
                        $this->compressContext->destroy();
                        $this->client->emit('debug', 'Destroyed compress context');
                    }
                    
                    $this->ws = null;
                    
                    if($this->wsStatus <= \CharlotteDunois\Yasmin\Client::WS_STATUS_CONNECTED) {
                        $this->wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_DISCONNECTED;
                    }
                    
                    $this->emit('close', $code, $reason);
                    $this->client->emit('disconnect', $code, $reason);
                    
                    if(\in_array($code, $this->wsCloseCodes['end'])) {
                        return $reject(new \Exception(self::WS_CLOSE_CODES[$code]));
                    }
                    
                    if($code === 1000 && $this->expectedClose) {
                        $this->gateway = null;
                        $this->queue = array();
                        
                        $this->wsSessionID = null;
                        $this->wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_IDLE;
                        
                        return;
                    }
                    
                    if($code === 1000 || ($code >= 4000 && !\in_array($code, $this->wsCloseCodes['resumable']))) {
                        $this->wsSessionID = null;
                    }
                    
                    if($code === 4002 && $this->encoding !== null && $this->encoding->getName() !== self::WS['encoding']) {
                        $this->encoding = null;
                        $this->gateway = \str_replace('encoding=etf', 'encoding=json', $this->gateway);
                        $this->client->emit('debug', 'Decoding payload error - Encoding ETF erroneous, falling back to default');
                    }
                    
                    $this->wsStatus = \CharlotteDunois\Yasmin\Client::WS_STATUS_RECONNECTING;
                    $this->renewConnection(false);
                });
            }, function($error) use ($resolve, $reject) {
                $this->client->emit('error', $error);
                
                if($this->ws) {
                    $this->ws->close(1006);
                }
                
                $this->renewConnection()->done($resolve, $reject);
            });
        }));
    }
    
    /**
     * Closes the WS connection.
     * @return void
     */
    function disconnect(int $code = 1000, string $reason = '') {
        if(!$this->ws) {
            return;
        }
        
        $this->processQueue();
        
        $this->client->emit('debug', 'Disconnecting from WS');
        
        $this->expectedClose = true;
        $this->ws->close($code, $reason);
    }
    
    /**
     * Closes the WS connection.
     * @return void
     */
    function reconnect(bool $resumable = true) {
        if(!$this->ws) {
            return;
        }
        
        if($resumable === false) {
            $this->wsSessionID = null;
        }
        
        $this->client->emit('debug', 'Disconnecting from WS in order to reconnect');
        $this->ws->close(4000, 'Reconnect required');
    }
    
    /**
     * Closes the WS connection.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function renewConnection(bool $forceNewGateway = true) {
        return $this->client->login(((string) $this->client->token), $forceNewGateway)->otherwise(function () use ($forceNewGateway) {
            $this->client->emit('debug', 'Error making new login after failed connection attempt... retrying in 30 seconds');
            
            return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($forceNewGateway) {
                $this->client->addTimer(30, function () use ($forceNewGateway, $resolve, $reject) {
                    $this->renewConnection($forceNewGateway)->done($resolve, $reject);
                });
            }));
        });
    }
    
    /**
     * @param array $packet
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \RuntimeException
     */
    function send(array $packet) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($packet) {
            if($this->wsStatus !== \CharlotteDunois\Yasmin\Client::WS_STATUS_NEARLY && $this->wsStatus !== \CharlotteDunois\Yasmin\Client::WS_STATUS_CONNECTED) {
                return $reject(new \RuntimeException('Unable to send WS message before a WS connection is established'));
            }
            
            $this->queue[] = array('packet' => $packet, 'resolve' => $resolve, 'reject' => $reject);
            
            if($this->running === false) {
                $this->processQueue();
            }
        }));
    }
    
    /**
     * Processes the WS queue.
     * @return void
     */
    function processQueue() {
         if($this->running) {
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
                 $element['reject'](new \Exception('No WS connection'));
                 break;
             }
             
             $this->_send($element['packet']);
             $element['resolve']();
         }
         
         $this->running = false;
    }
    
    /**
     * Set authenticated.
     * @return void
     */
    function setAuthenticated(bool $state) {
        $this->authenticated = $state;
    }
    
    /**
     * Get last identified timestamp
     * @return int
     */
    function getLastIdentified() {
        return $this->lastIdentify;
    }
    
    /**
     * Get the session ID.
     * @return string|null
     */
    function getSessionID() {
        return $this->wsSessionID;
    }
    
    /**
     * Set the session ID.
     * @return void
     */
    function setSessionID(?string $id) {
        $this->wsSessionID = $id;
    }
    
    /**
     * Sends an IDENTIFY or RESUME payload, depending on ws session ID.
     * @return void
     */
    function sendIdentify() {
        $this->authenticated = false;
        
        if(empty($this->client->token)) {
            throw new \RuntimeException('No client token to start with');
        }
        
        $op = self::OPCODES['IDENTIFY'];
        if(empty($this->wsSessionID)) {
            $this->client->emit('debug', 'Sending IDENTIFY packet to WS');
        } else {
            $op = self::OPCODES['RESUME'];
            $this->client->emit('debug', 'Sending RESUME packet to WS');
        }
        
        $packet = array(
            'op' => $op,
            'd' => array(
                'token' => $this->client->token,
                'properties' => array(
                    '$os' => \php_uname('s'),
                    '$browser' => 'Yasmin '.\CharlotteDunois\Yasmin\Client::VERSION,
                    '$device' => 'Yasmin '.\CharlotteDunois\Yasmin\Client::VERSION
                ),
                'compress' => ($this->compressContext ? $this->compressContext->isPayloadCompression() : false),
                'large_threshold' => ((int) $this->client->getOption('ws.largeThreshold', 250)),
                'shard' => array(
                    ((int) $this->client->getOption('shardID', 0)),
                    ((int) $this->client->getOption('shardCount', 1))
                )
            )
        );
        
        $presence = (array) $this->client->getOption('ws.presence', array());
        if(\is_array($presence) && !empty($presence)) {
            $packet['d']['presence'] = $presence;
        }
        
        if($op === self::OPCODES['RESUME']) {
            $packet['d']['session_id'] = $this->wsSessionID;
            $packet['d']['seq'] = ($this->previous && $this->wshandler->previousSequence !== null ? $this->wshandler->previousSequence : $this->wshandler->sequence);
        }
        
        $this->_send($packet);
    }
    
    /**
     * Sends a heartbeat.
     * @return void
     */
    function heartbeat() {
        if($this->wsHeartbeat['ack'] === false) {
            return $this->heartFailure();
        }
        
        if(!$this->authenticated) {
            return; // Do not heartbeat if unauthenticated
        }
        
        $this->client->emit('debug', 'Sending WS heartbeat at sequence '.$this->wshandler->sequence);
        
        $this->wsHeartbeat['ack'] = false;
        $this->wsHeartbeat['dateline'] = microtime(true);
        
        $this->_send(array(
            'op' => self::OPCODES['HEARTBEAT'],
            'd' => $this->wshandler->sequence
        ));
    }
    
    /**
     * Handles heart failures.
     * @return void
     */
    function heartFailure() {
        $this->client->emit('debug', 'WS heart failure');
        $this->disconnect(1006, 'No heartbeat ack');
    }
    
    /**
     * Handles heartbeat ack.
     * @return void
     */
    function _pong($end) {
        $time = \ceil(($end - $this->wsHeartbeat['dateline']) * 1000);
        $this->client->pings[] = (int) $time;
        
        $pings = \count($this->client->pings);
        if($pings > 3) {
            $this->client->pings = \array_slice($this->client->pings, ($pings - 3));
        }
        
        $this->wsHeartbeat['ack'] = true;
        $this->wsHeartbeat['dateline'] = 0;
        
        $this->client->emit('debug', 'Received WS heartbeat ACK');
    }
    
    /**
     * Direct ws send method. DO NOT USE.
     * @return void
     */
    function _send(array $packet) {
        if(!$this->ws) {
            $this->client->emit('debug', 'Tried sending a WS packet with no WS connection');
            return;
        }
        
        $data = $this->encoding->encode($packet);
        $msg = $this->encoding->prepareMessage($data);
        
        $this->client->emit('debug', 'Sending WS packet with OP code '.$packet['op']);
        $this->ws->send($msg);
    }
}
