<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

class Dispatch {
    private $wsevents = array();
    protected $wshandler;
    
    function __construct($wshandler) {
        $this->wshandler = $wshandler;
        
        $this->register('READY', '\CharlotteDunois\Yasmin\WebSocket\Events\Ready');
        $this->register('RESUMED', '\CharlotteDunois\Yasmin\WebSocket\Events\Resumed');
    }
    
    function getEvent($name) {
        if(isset($this->wsevents[$name])) {
            return $this->wsevents[$name];
        }
        
        throw new \Exception('Can not find WS event');
    }
    
    function handle($packet) { //TODO
        if(isset($this->wsevents[$packet['t']])) {
            try {
                $this->wshandler->client()->emit('debug', 'Received WS event '.$packet['t']);
                
                if(in_array($packet['t'], $this->wshandler->client()->getOption('disabledEvents', array()))) {
                    $this->wshandler->client()->emit('debug', 'WS event '.$packet['t'].' is disabled, skipping...');
                    return;
                }
                
                $this->wsevents[$packet['t']]->handle($packet['d']);
            } catch(\Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }
    
    private function register($name, $class) {
        $this->wsevents[$name] = new $class($this->wshandler->client());
    }
}
