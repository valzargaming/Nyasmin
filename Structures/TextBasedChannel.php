<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class TextBasedChannel extends Structure
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface { //TODO: Implementation
                    
    public $messages;
    
    protected $id;
    protected $type;
    protected $lastMessageID;
    
    protected $icon;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, $channel) {
        parent::__construct($client);
        
        $this->messages = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPE[$channel['type']];
        $this->lastMessageID = $channel['last_message_id'] ?? null;
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'lastMessage':
                if(!empty($this->lastMessageID) && $this->messages->has($this->lastMessageID)) {
                    return $this->messages->get($this->lastMessageID);
                }
            break;
        }
        
        return null;
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
    
    function search(array $options = array()) {
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
