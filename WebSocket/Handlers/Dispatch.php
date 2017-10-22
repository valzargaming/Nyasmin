<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

/**
 * WS Event handler
 * @access private
 */
class Dispatch {
    private $wsevents = array();
    protected $wshandler;
    
    function __construct(\CharlotteDunois\Yasmin\WebSocket\WSHandler $wshandler) {
        $this->wshandler = $wshandler;
        
        $allEvents = array(
            'RESUMED' => '\CharlotteDunois\Yasmin\WebSocket\Events\Resumed',
            'READY' => '\CharlotteDunois\Yasmin\WebSocket\Events\Ready',
            'CHANNEL_CREATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelCreate',
            'CHANNEL_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelUpdate',
            'CHANNEL_DELETE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelDelete',
            'GUILD_CREATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildCreate',
            'PRESENCE_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\PresenceUpdate'
        );
        
        $events = \array_diff($allEvents, (array) $this->wshandler->client->getOption('disabledEvents', array()));
        foreach($events as $name => $class) {
            $this->register($name, $class);
        }
    }
    
    function getEvent(string $name) {
        if(isset($this->wsevents[$name])) {
            return $this->wsevents[$name];
        }
        
        throw new \Exception('Can not find WS event');
    }
    
    function handle(array $packet) {
        $this->wshandler->client->emit('debug', 'Received WS event '.$packet['t']);
        
        if(isset($this->wsevents[$packet['t']])) {
            try {
                if(in_array($packet['t'], $this->wshandler->client->getOption('disabledEvents', array()))) {
                    $this->wshandler->client->emit('debug', 'WS event '.$packet['t'].' is disabled, skipping...');
                    return;
                }
                
                $this->wshandler->client->emit('debug', 'Handling WS event '.$packet['t']);
                
                $this->wsevents[$packet['t']]->handle($packet['d']);
            } catch(\Exception $e) {
                $this->wshandler->client->emit('error', $e);
            }
        }
    }
    
    private function register($name, $class) {
        $this->wsevents[$name] = new $class($this->wshandler->client);
    }
}
