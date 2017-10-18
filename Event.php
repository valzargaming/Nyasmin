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
 * The class that gets emitted using our Event Emitter.
 */
class Event implements \League\Event\EventInterface { //TODO: Docs
    private $emitter = null;
    private $propagation = false;
    private $name = null;
    private $params = array();
    
    /**
     * The constructor.
     * @param string $name
     * @param mixed ...$args
     */
    function __construct($name, ...$args) {
        $this->name = $name;
        $this->params = $args;
    }
    
    /**
     * Gets the event name.
     */
    function getName() {
        return $this->name;
    }
    
    /**
     * Gets all passed parameters.
     */
    function getParams() {
        return $this->params;
    }
    
    /**
     * Get a specific paramter.
     */
    function getParam($index) {
        return $this->params[$index] ?? null;
    }
    
    /**
     * Something something.
     * @access private
     */
    function isPropagationStopped() {
        return $this->propagation;
    }
    
    /**
     * Something something.
     * @access private
     */
    function stopPropagation() {
        $this->propagation = false;
        return $this;
    }
    
    /**
     * Set emitter.
     * @access private
     */
    function setEmitter(\League\Event\EmitterInterface $emitter) {
        $this->emitter = $emitter;
        return $this;
    }
    
    /**
     * Get emitter.
     * @access private
     */
    function getEmitter() {
        return $this->emitter;
    }
}
