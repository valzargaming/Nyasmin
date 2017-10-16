<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class TextChannel extends Structure
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface { //TODO
        
    protected $type = 'text';
    
    function __construct($client, $channel) {
        parent::__construct($client);
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return NULL;
    }
    
    function acknowledge() {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function awaitMessages(callable $filter, array $options = array()) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function bulkDelete($messages) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function send(string $message, array $options = array()) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function isRecipient($user) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function startTyping() {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function stopTyping(bool $force = false) {
        if($this->typingCount() === 0) {
            return \React\Promise\resolve();
        }
        
        
    }
    
    function typingCount() {
        
    }
    
    function typingIn($user) {
        
    }
    
    function isTypingSince($user) {
        
    }
}
