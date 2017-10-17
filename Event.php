<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin;

class Event implements \League\Event\EventInterface { //TODO: Docs
    
    private $emitter = null;
    private $propagation = false;
    private $name = null;
    private $params = array();
    
    function __construct($name, ...$args) {
        $this->name = $name;
        $this->params = $args;
    }
    
    function getName() {
        return $this->name;
    }
    
    function getParams() {
        return $this->params;
    }
    
    function getParam($index) {
        return $this->params[$index] ?? null;
    }
    
    function isPropagationStopped() {
        return $this->propagation;
    }
    
    function stopPropagation() {
        $this->propagation = false;
        return $this;
    }
    
    function setEmitter(\League\Event\EmitterInterface $emitter) {
        $this->emitter = $emitter;
        return $this;
    }
    
    function getEmitter() {
        return $this->emitter;
    }
}
