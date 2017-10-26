<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Structures;

class TextBasedChannel extends Structure
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface { //TODO: Implementation
                    
    protected $messages;
    protected $typings;
    
    protected $id;
    protected $type;
    protected $lastMessageID;
    
    protected $createdTimestamp;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, $channel) {
        parent::__construct($client);
        
        $this->messages = new \CharlotteDunois\Yasmin\Structures\Collection();
        $this->typings = new \CharlotteDunois\Yasmin\Structures\Collection();
        
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
            case 'messages':
                return $this->messages;
            break;
        }
        
        return null;
    }
    
    function acknowledge() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        }));
    }
    
    function awaitMessages(callable $filter, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        }));
    }
    
    function bulkDelete($messages) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        }));
    }
    
    function search(array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        }));
    }
    
    function send(string $message, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($message, $options) {
            if(!empty($options['files'])) {
                $promises = array();
                foreach($options['files'] as $file) {
                    if(\is_string($file)) {
                        if(filter_var($file, FILTER_VALIDATE_URL)) {
                            $promises[] = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file)->then(function ($data) use ($file) {
                                return array('name' => \basename($file), 'data' => $data);
                            });
                        } else {
                            $promises[] = \React\Promise\resolve(array('name' => 'file-'.\bin2hex(\random_bytes(3)).'.jpg', 'data' => $file));
                        }
                        
                        continue;
                    }
                    
                    if(!\is_array($file)) {
                        continue;
                    }
                    
                    if(!isset($file['name'])) {
                        if(isset($file['path'])) {
                            $file['name'] = \basename($file['path']);
                        } else {
                            $file['name'] = 'file-'.\bin2hex(\random_bytes(3)).'.jpg';
                        }
                    }
                    
                    if(!isset($file['data']) && filter_var($file['path'], FILTER_VALIDATE_URL)) {
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
                
                $this->client->apimanager()->endpoints->channel->createMessage($this->id, $msg, ($files ?? array()))->then(function ($response) use ($resolve) {
                    $resolve($this->_createMessage($response));
                }, $reject);
            });
        }));
    }
    
    function isRecipient($user) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        }));
    }
    
    function startTyping() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        }));
    }
    
    function stopTyping(bool $force = false) {
        if($this->typingCount() === 0) {
            return \React\Promise\resolve();
        }
        
        
    }
    
    function typingCount() {
        return $this->typings->count();
    }
    
    function isTyping(\CharlotteDunois\Yasmin\Structures\User $user) {
        return $this->typings->get($user->id);
    }
    
    function isTypingSince(\CharlotteDunois\Yasmin\Structures\User $user) {
        if($this->isTyping($user) === false) {
            return -1;
        }
        
        return (\time() - $this->typings->get($user->id)['timestamp']);
    }
    
    function _createMessage(array $message) {
        if($this->messages->has($message['id'])) {
            return $this->messages->get($message['id']);
        }
        
        $msg = new \CharlotteDunois\Yasmin\Structures\Message($this->client, $this, $message);
        $this->messages->set($msg->id, $msg);
        return $msg;
    }
    
    function _updateTyping(\CharlotteDunois\Yasmin\Structures\User $user, int $timestamp = null) {
        if($timestamp === null) {
            return $this->typings->delete($user->id);
        }
        
        $timer = $this->client->addTimer(6, function ($client) use ($user) {
            $this->typings->delete($user->id);
            $client->emit('typingStop', $this, $user);
        });
        
        $this->typings->set($user->id, array(
            'timestamp' => (int) $timestamp,
            'timer' => $timer
        ));
    }
}
