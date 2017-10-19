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
 * Our Event Emitter.
 */
class EventEmitter {
    /**
     * @var array
     * @access private
     */
    protected $listeners = array();
    
    /**
     * @var array
     * @access private
     */
    protected $onceListeners = array();
    
    /**
     * Attach a listener to an event.
     * @param string    $event
     * @param callable  $listener
     * @return this
     */
    function on($event, callable $listener) {
        if($event === null) {
            throw new \InvalidArgumentException('event name must not be null');
        }
        
        if(!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = $listener;
        
        return $this;
    }
    
    /**
     * Attach a listener to an event, for exactly once.
     * @param string    $event
     * @param callable  $listener
     * @return this
     */
    function once($event, callable $listener) {
        if($event === null) {
            throw new \InvalidArgumentException('event name must not be null');
        }
        
        if (!isset($this->onceListeners[$event])) {
            $this->onceListeners[$event] = [];
        }
        
        $this->onceListeners[$event][] = $listener;
        
        return $this;
    }
    
    /**
     * Remove specified listener from an event.
     * @param string    $event
     * @param callable  $listener
     * @return this
     */
    function removeListener($event, callable $listener) {
        if($event === null) {
            throw new \InvalidArgumentException('event name must not be null');
        }
        
        if(isset($this->listeners[$event])) {
            $index = \array_search($listener, $this->listeners[$event], true);
            if($index !== $false) {
                unset($this->listeners[$event][$index]);
                if (\count($this->listeners[$event]) === 0) {
                    unset($this->listeners[$event]);
                }
            }
        }
        
        if(isset($this->onceListeners[$event])) {
            $index = \array_search($listener, $this->onceListeners[$event], true);
            if($index !== false) {
                unset($this->onceListeners[$event][$index]);
                if (\count($this->onceListeners[$event]) === 0) {
                    unset($this->onceListeners[$event]);
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Remove all listeners from an event (or all listeners).
     * @param string|null  $event
     * @return this
     */
    function removeAllListeners($event = null) {
        if($event !== null) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners = [];
        }
        
        if($event !== null) {
            unset($this->onceListeners[$event]);
        } else {
            $this->onceListeners = [];
        }
        
        return $this;
    }

    /**
     * Get listeners for a specific events, or all listeners.
     * @param string|null  $event
     * @return array
     */
    function listeners($event = null) {
        if($event === null) {
            $events = array();
            $eventNames = \array_unique(\array_merge(\array_keys($this->listeners), \array_keys($this->onceListeners)));
            
            foreach($eventNames as $eventName) {
                $events[$eventName] = \array_merge(
                    (isset($this->listeners[$eventName]) ? $this->listeners[$eventName] : array()),
                    (isset($this->onceListeners[$eventName]) ? $this->onceListeners[$eventName] : array())
                );
            }
            
            return $events;
        }
        
        return \array_merge(
            (isset($this->listeners[$event]) ? $this->listeners[$event] : array()),
            (isset($this->onceListeners[$event]) ? $this->onceListeners[$event] : array())
        );
    }
    
    /**
     * Emits an event.
     * @param string  $event
     * @param mixed   $arguments
     */
    function emit($event, ...$arguments) {
        if($event === null) {
            throw new \InvalidArgumentException('event name must not be null');
        }
        
        if(isset($this->listeners[$event])) {
            foreach($this->listeners[$event] as $listener) {
                $listener(...$arguments);
            }
        }
        
        if(isset($this->onceListeners[$event])) {
            $listeners = $this->onceListeners[$event];
            unset($this->onceListeners[$event]);
            foreach($listeners as $listener) {
                $listener(...$arguments);
            }
        }
    }
}
