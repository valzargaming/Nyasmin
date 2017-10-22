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
    
    protected $createdTimestamp;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, $channel) {
        parent::__construct($client);
        
        $this->messages = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPE[$channel['type']];
        $this->lastMessageID = $channel['last_message_id'] ?? null;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
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
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($message, $options) {
            if(!empty($options['files'])) {
                $promises = array();
                foreach($options['files'] as $file) {
                    if(\is_string($file)) {
                        if(filter_var($file, FILTER_VALIDATE_URL)) {
                            $promises[] = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file)->then(function ($data) use ($file) {
                                return array('name' => \basename($file), 'data' => $data, 'filename' => \basename($file));
                            });
                        } else {
                            $promises[] = \React\Promise\resolve(array('name' => 'image.jpg', 'data' => $file, 'filename' => 'image.jpg'));
                        }
                        
                        continue;
                    }
                    
                    if(!\is_array($file)) {
                        continue;
                    }
                    
                    if(!isset($file['filename'])) {
                        if(isset($file['path'])) {
                            $file['filename'] = \basename($file['path']);
                        } else {
                            $file['filename'] = 'image.jpg';
                        }
                    }
                    
                    if(!isset($file['name'])) {
                        $file['name'] = 'file-'.\bin2hex(\random_bytes(3));
                    }
                    
                    if(isset($file['data'])) {
                        $promises[] = \React\Promise\resolve($file);
                        continue;
                    }
                    
                    if(filter_var($file['path'], FILTER_VALIDATE_URL)) {
                        $promises[] = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file['path'])->then(function ($data) use ($file) {
                            $file['data'] = $data;
                            return $file;
                        });
                    } else {
                        $promises[] = \React\Promise\resolve($file);
                    }
                }
                
                $files = \React\Promise\all($promises);
            } else {
                $files = \React\Promise\resolve();
            }
            
            $files->then(function ($files = null) use ($message, $options, $resolve, $reject) {
                $msg = array(
                    'content' => $message
                );
                
                if(!empty($options['embed'])) {
                    $msg['embed'] = $options['embed'];
                }
                
                $this->client->apimanager()->endpoints->createMessage($this->id, $msg, ($files ?? array()))->then(function ($response) use ($resolve) {
                    $resolve($this->_createMessage($response));
                }, $reject);
            });
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
    
    function _createMessage(array $message) {
        if($this->messages->has($message['id'])) {
            return $this->messages->get($message['id']);
        }
        
        $msg = new \CharlotteDunois\Yasmin\Structures\Message($this->client, $this, $message);
        $this->messages->set($msg->id, $msg);
        return $msg;
    }
}
