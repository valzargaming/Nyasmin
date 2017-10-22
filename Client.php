<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin;
use CharlotteDunois\Yasmin\WebSocket\Events\GuildMemberAdd;
use CharlotteDunois\Yasmin\WebSocket\Events\MessageDeleteBulk;

/**
 * The client. What else do you expect this to say?
 */
class Client extends EventEmitter { //TODO: Implementation
    /**
     * It holds all cached channels.
     * @var \CharlotteDunois\Yasmin\Structures\ChannelStorage
     */
    public $channels;
    
    /**
     * It holds all guilds.
     * @var \CharlotteDunois\Yasmin\Structures\GuildStorage
     */
    public $guilds;
    
    /**
     * It holds all cached presences.
     * @var \CharlotteDunois\Yasmin\Structures\PresenceStorage
     * @access private
     */
    public $presences;
    
    /**
     * It holds all cached users.
     * @var \CharlotteDunois\Yasmin\Structures\UserStorage
     */
    public $users;
    
    /**
     * It holds all open Voice Connections.
     * @var \CharlotteDunois\Yasmin\Structures\Collection
     */
    public $voiceConnections;
    
    /**
     * The last 3 websocket pings (in ms).
     * @var int[]
     */
    public $pings = array();
    
    /**
     * The UNIX timestamp of the last emitted ready event (or null if none yet).
     * @var int|null
     */
    public $readyTimestamp = null;
    
    /**
     * The token.
     * @var string|null
     */
    public $token;
    
    /**
     * The Event Loop.
     * @var \React\EventLoop\LoopInterface
     * @access private
     */
    private $loop;
    
    /**
     * Client Options.
     * @var array
     * @access private
     */
    private $options = array();
    
    /**
     * The Client User.
     * @var \CharlotteDunois\Yasmin\Structures\ClientUser
     * @access private
     */
    private $user;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     * @access private
     */
    private $api;
    
    /**
     * @var \CharlotteDunois\Yasmin\WebSocket\WSManager
     * @access private
     */
    private $ws;
    
    /**
     * Gateway address.
     * @var string
     * @access private
     */
    private $gateway;
    
    /**
     * Timers which automatically get cancelled on destroy and only get run when we have a WS connection.
     * @var \React\EventLoop\Timer\Timer[]
     * @access private
     */
     private $timers = array();
    
    /**
     * What do you expect this to do?
     * @param array                           $options  Any client options.
     * @param \React\EventLoop\LoopInterface  $loop     You can pass an Event Loop to the class, or it will automatically create one (you still need to make it run yourself).
     * @return this
     *
     * @event ready
     * @event disconnect
     * @event reconnect
     * @event channelCreate
     * @event channelUpdate
     * @event channelDelete
     * @event guildCreate
     * @event guildUpdate
     * @event guildDelete
     * @event guildBanAdd
     * @event guildBanRemove
     * @event guildMemberAdd
     * @event guildMemberRemove
     * @event guildMembersChunk
     * @event roleCreate
     * @event roleUpdate
     * @event roleDelete
     * @event message
     * @event messageUpdate
     * @event messageDelete
     * @event messageDeleteBulk
     * @event presenceUpdate
     *
     * @event raw
     * @event messageDeleteRaw
     * @event messageDeleteBulkRaw
     * @event error
     * @event debug
     */
    function __construct(array $options = array(), \React\EventLoop\LoopInterface $loop = null) {
        if(!$loop) {
            $loop = \React\EventLoop\Factory::create();
        }
        
        \CharlotteDunois\Yasmin\Utils\URLHelpers::setLoop($loop);
        
        if(!empty($options)) {
            $this->validateClientOptions($options);
            $this->options = \array_merge($this->options, $options);
        }
        
        $this->loop = $loop;
        
        $this->api = new \CharlotteDunois\Yasmin\HTTP\APIManager($this);
        $this->ws = new \CharlotteDunois\Yasmin\WebSocket\WSManager($this);
        
        $this->channels = new \CharlotteDunois\Yasmin\Structures\ChannelStorage($this);
        $this->guilds = new \CharlotteDunois\Yasmin\Structures\GuildStorage($this);
        $this->presences = new \CharlotteDunois\Yasmin\Structures\PresenceStorage($this);
        $this->users = new \CharlotteDunois\Yasmin\Structures\UserStorage($this);
        $this->voiceConnections = new \CharlotteDunois\Yasmin\Structures\Collection();
    }
    
    /**
     * You don't need to know.
     * @return \CharlotteDunois\Yasmin\HTTP\APIManager
     * @access private
     */
    function apimanager() {
        return $this->api;
    }
    
    /**
     * You don't need to know.
     * @return \CharlotteDunois\Yasmin\WebSocket\WSManager
     * @access private
     */
    function wsmanager() {
        return $this->ws;
    }
    
    /**
     * Get the React Event Loop that is stored in this class.
     * @return \React\EventLoop\LoopInterface
     */
    function getLoop() {
        return $this->loop;
    }
    
    /**
     * Get the Client User instance.
     * @return \CharlotteDunois\Yasmin\Structures\ClientUser|null
     */
    function getClientUser() {
        return $this->user;
    }
    
    /**
     * Get a specific option, or the default value.
     * @param string  $name
     * @param mixed   $default
     * @return mixed
     */
    function getOption($name, $default = null) {
        if(isset($this->options[$name])) {
            return $this->options[$name];
        }
        
        return $default;
    }
    
    /**
     * Gets the average ping.
     * @return int
     */
    function getPing() {
        $cpings = \count($this->pings);
        if($cpings === 0) {
            return \NAN;
        }
        
        return \ceil(\array_sum($this->pings) / $cpings);
    }
    
    /**
     * Returns the WS status.
     * @return int
     */
    function getWSstatus() {
        return $this->ws->status;
    }
    
    /**
     * Login into Discord. Opens a WebSocket Gateway connection. Resolves once a WebSocket connection has been established (does not mean the client is ready).
     * @param string $token  Your token.
     * @param bool   $force  Forces the client to get the gateway address from Discord.
     * @return \React\Promise\Promise<null>
     */
    function login(string $token, bool $force = false) {
        $this->token = $token;
        
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($force) {
            if($this->gateway && !$force) {
                $gateway = \React\Promise\resolve($this->gateway);
            } else {
                if($this->gateway) {
                    $gateway = $this->api->getGateway();
                } else {
                    $gateway = $this->api->getGatewaySync();
                }
                
                $gateway = $gateway->then(function ($response) {
                    return $response['url'];
                });
            }
            
            $gateway->then(function ($url) use ($resolve, $reject) {
                $this->gateway = $url;
                $url .= '?v='.\CharlotteDunois\Yasmin\Constants::WS['version'].'&encoding='.\CharlotteDunois\Yasmin\Constants::WS['encoding'];
                
                $this->ws->connect($url)->then($resolve, $reject);
                $this->ws->once('ready', function () {
                    $this->emit('ready');
                });
            });
        });
    }
    
    /**
     * Cleanly logs out of Discord.
     * @return \React\Promise\Promise<null>
     */
    function destroy() {
        return new \React\Promise\Promise(function (callable $resolve) {
            foreach($this->timers as $key => &$timer) {
                $timer['timer']->cancel();
                unset($this->timers[$key], $timer);
            }
            
            $this->api->destroy();
            $this->ws->disconnect();
            $resolve();
        });
    }
    
    /**
     * Fetches an User from the API.
     * @param string  $userid  The User ID to fetch.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Structures\User>
     */
    function fetchUser(string $userid) {
        return new \React\Promise\Promise(function (callable $resolve, $reject) use  ($userid) {
            $this->api->endpoints->getUser($userid)->then(function ($user) use ($resolve) {
                $user = $this->users->factory($user);
                $resolve($user);
            }, $reject);
        });
    }
    
    /**
     * Adds a "client-dependant" timer (only gets run during an established WS connection). The timer gets automatically cancelled on destroy. The callback can only accept one argument, the client.
     * @param float|int  $timeout
     * @param callable   $callback
     * @param bool       $ignoreWS
     * @return \React\EventLoop\Timer\Timer
     */
    function addTimer(float $timeout, callable $callback, bool $ignoreWS = false) {
        $timer = $this->loop->addTimer($timeout, function () use ($callback, $ignoreWS) {
            if($ignoreWS || $this->getWSstatus() === \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTED) {
                $callback($this);
            }
        });
        
        $this->timers[] = array('type' => 1, 'timer' => $timer);
        return $timer;
    }
    
    /**
     * Adds a "client-dependant" periodic timer (only gets run during an established WS connection). The timer gets automatically cancelled on destroy. The callback can only accept one argument, the client.
     * @param float|int  $interval
     * @param callable   $callback
     * @param bool       $ignoreWS
     * @return \React\EventLoop\Timer\Timer
     */
    function addPeriodicTimer(float $interval, callable $callback, bool $ignoreWS = false) {
        $timer = $this->loop->addPeriodicTimer($interval, function () use ($callback, $ignoreWS) {
            if($ignoreWS || $this->getWSstatus() === \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTED) {
                $callback($this);
            }
        });
        
        $this->timers[] = array('type' => 0, 'timer' => $timer);
        return $timer;
    }
    
    /**
     * Cancels a timer.
     * @param \React\EventLoop\Timer\Timer  $timer
     * @return bool
     */
    function cancelTimer(\React\EventLoop\Timer\Timer $timer) {
        $timer->cancel();
        $key = \array_search($timer, $this->timers, true);
        if($key !== false) {
            unset($this->timers[$key]);
        }
        
        return true;
    }
    
    /**
     * Make an instance of {ClientUser} and store it.
     * @access private
     */
    function setClientUser(array $user) {
        $this->user = new \CharlotteDunois\Yasmin\Structures\ClientUser($this, $user);
    }
    
    /**
     * Emit an event.
     * @access private
     */
    function emit($name, ...$args) {
        if($name === 'debug' && $this->getOption('disableDebugEvent', false) === true) {
            return;
        }
        
        parent::emit($name, ...$args);
    }
    
    /**
     * Validates the passed client options.
     * @param array
     * @throws \Exception
     */
    private function validateClientOptions(array $options) {
        
    }
}
