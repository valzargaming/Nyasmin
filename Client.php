<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin;

class Client extends \League\Event\Emitter {
    public $channels;
    public $guilds;
    public $presences;
    public $users;
    public $voiceConnections;
    
    public $pings = array();
    public $readyTimestamp = NULL;
    
    private $loop;
    private $options = array();
    public $token;
    private $ws;
    private $user;
    
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
    
    function wsmanager() {
        return $this->ws;
    }
    
    function getLoop() {
        return $this->loop;
    }
    
    function getClientUser() {
        return $this->user;
    }
    
    function getOption($name, $default = NULL) {
        if(isset($this->options[$name])) {
            return $this->options[$name];
        }
        
        return $default;
    }
    
    function getPing() {
        $cpings = count($this->pings);
        if($cpings === 0) {
            return \NAN;
        }
        
        return ceil(array_sum($this->pings) / $cpings);
    }
    
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
    
    function setClientUser(array $user) {
        $this->user = new \CharlotteDunois\Yasmin\Structures\ClientUser($this, $user);
    }
    
    function _pong($end) {
        $time = ceil(($end - $this->ws->wsHeartbeat['dateline']) * 1000);
        $this->pings[] = $time;
        
        if(count($this->pings) > 3) {
            $this->pings = array_slice($this->pings, 0, 3);
        }
    }
    
    function on($name, $listener) {
        return $this->addListener($name, $listener);
    }
    
    function once($name, $listener) {
        return $this->addOneTimeListener($name, $listener);
    }
    
    function emit($name, ...$args) {
        if($this->getOption('disableDebugEvent', false) === true) {
            return;
        }
        
        $event = new \CharlotteDunois\Yasmin\Event($name, ...$args);
        $event->setEmitter($this);
        return parent::emit($event);
    }
}
