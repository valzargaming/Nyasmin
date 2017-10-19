<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin;

/**
 * The client. What else do you expect this to say?
 */
class Client extends EventEmitter { //TODO: Implementation
    /**
     * @var \CharlotteDunois\Yasmin\Structures\ChannelStorage It holds all cached channels.
     */
    public $channels;
    
    /**
     * @var \CharlotteDunois\Yasmin\Structures\GuildStorage It holds all guilds.
     */
    public $guilds;
    
    /**
     * @var \CharlotteDunois\Yasmin\Structures\PresenceStorage It holds all cached presences.
     * @access private
     */
    public $presences;
    
    /**
     * @var \CharlotteDunois\Yasmin\Structures\UserStorage It holds all cached users.
     */
    public $users;
    
    /**
     * @var \CharlotteDunois\Yasmin\Structures\Collection It holds all open Voice Connections.
     */
    public $voiceConnections;
    
    /**
     * @var int[] The last 3 websocket pings (in ms).
     */
    public $pings = array();
    
    /**
     * @var int|null The UNIX timestamp of the last emitted ready event (or null if none yet).
     */
    public $readyTimestamp = null;
    
    /**
     * @var string|null The token.
     */
    public $token;
    
    /**
     * @access private
     */
    private $loop;
    
    /**
     * @access private
     */
    private $options = array();
    
    /**
     * @access private
     */
    private $user;
    
    /**
     * @access private
     */
    private $ws;
    
    /**
     * What do you expect this to do?
     * @param array                           $options  Any client options.
     * @param \React\EventLoop\LoopInterface  $loop     You can pass an Event Loop to the class, or it will automatically create one (you still need to make it run yourself).
     * @return this
     */
    function __construct(array $options = array(), \React\EventLoop\LoopInterface $loop = null) {
        if(!$loop) {
            $loop = \React\EventLoop\Factory::create();
        }
        
        $this->options = array_merge($this->options, $options);
        
        $this->loop = $loop;
        $this->ws = new \CharlotteDunois\Yasmin\WebSocket\WSManager($this);
        
        $this->channels = new \CharlotteDunois\Yasmin\Structures\ChannelStorage($this);
        $this->guilds = new \CharlotteDunois\Yasmin\Structures\GuildStorage($this);
        $this->presences = new \CharlotteDunois\Yasmin\Structures\PresenceStorage($this);
        $this->users = new \CharlotteDunois\Yasmin\Structures\UserStorage($this);
        $this->voiceConnections = new \CharlotteDunois\Yasmin\Structures\Collection();
    }
    
    /**
     * You don't need to know.
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
     * Login into Discord. Opens a WebSocket Gateway connection.
     * @param string $token Your token.
     * @return \React\Promise\Promise<null>
     */
    function login(string $token) {
        $this->token = $token;
        
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $url = \CharlotteDunois\Yasmin\Constants::WS['baseurl'].'?v='.\CharlotteDunois\Yasmin\Constants::WS['version'].'&encoding='.\CharlotteDunois\Yasmin\Constants::WS['encoding'];
            
            $connect = $this->ws->connect($url);
            if($connect) {
                $connect->then($resolve, $reject);
                $resolve = function () { };
            }
            
            $errorLn = function ($error) use ($reject) {
                $reject($error);
            };
            
            $this->ws->once('error', $errorLn);
            
            $this->ws->once('ready', function () use ($errorLn, $resolve) {
                $this->ws->removeListener('error', $errorLn);
                
                $resolve();
                $this->emit('ready');
            });
        });
    }
    
    /**
     * Cleanly logs out of Discord.
     * @return \React\Promise\Promise<null>
     */
    function destroy() {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->ws->disconnect();
            $resolve();
        });
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
}
