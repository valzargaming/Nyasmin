<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\NekoCord;

class Event implements \League\Event\EventInterface {
    
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
        return $this->params[$index] ?? NULL;
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
