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
    public $channels;
    public $guilds;
    public $presences;
    public $users;
    public $voiceConnections;
    
    /**
     * The last 3 websocket pings (in ms).
     * @property int[] $pings
     */
    public $pings = array();
    
    /**
     * The UNIX timestamp of the last emitted ready event (or null if none yet).
     * @property int|null $readyTimestamp
     */
    public $readyTimestamp = null;
    
    private $loop;
    private $options = array();
    public $token;
    
    private $user;
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
     * @param string $name
     * @param mixed $default
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
            
            $this->ws->once('ready', function () use ($resolve, &$listener) {
                $resolve();
                $this->emit('ready');
            });
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
        
        return parent::emit($name, ...$args);
    }
}
