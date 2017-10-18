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
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
        
        $this->register('READY', '\CharlotteDunois\Yasmin\WebSocket\Events\Ready');
        $this->register('RESUMED', '\CharlotteDunois\Yasmin\WebSocket\Events\Resumed');
        
        $this->register('CHANNEL_CREATE', '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelCreate');
        $this->register('CHANNEL_UPDATE', '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelUpdate');
        $this->register('CHANNEL_DELETE', '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelDelete');
        
        $this->register('GUILD_CREATE', '\CharlotteDunois\Yasmin\WebSocket\Events\GuildCreate');
        
        $this->register('PRESENCE_UPDATE', '\CharlotteDunois\Yasmin\WebSocket\Events\PresenceUpdate');
    }
    
    function getEvent($name) {
        if(isset($this->wsevents[$name])) {
            return $this->wsevents[$name];
        }
        
        throw new \Exception('Can not find WS event');
    }
    
    function handle($packet) {
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
