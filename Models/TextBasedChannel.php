<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

class TextBasedChannel extends ClientBase
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
        
        $this->messages = new \CharlotteDunois\Yasmin\Models\Collection();
        $this->typings = new \CharlotteDunois\Yasmin\Models\Collection();
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES[$channel['type']];
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
                        if(\filter_var($file, FILTER_VALIDATE_URL)) {
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
                
                if(!empty($options['split'])) {
                    $split = array('before' => '', 'after' => '', 'char' => "\n", 'maxLength' => 1950);
                    if(\is_array($options['split'])) {
                        $split = \array_merge($split, $options['split']);
                    }
                    
                    if(\strlen($msg['content']) > $split['maxLength']) {
                        $collection = new \CharlotteDunois\Yasmin\Models\Collection();
                        
                        $chunkedSend = function ($msg, $files = null) use ($collection, $reject) {
                            return $this->client->apimanager()->endpoints->channel->createMessage($this->id, $msg, ($files ?? array()))->then(function ($response) use ($collection) {
                                $msg = $this->_createMessage($response);
                                $collection->set($msg->id, $msg);
                            }, $reject);
                        };
                        
                        $i = 0;
                        $messages = array();
                        
                        $parts = \explode($split['char'], $msg['content']);
                        foreach($parts as $part) {
                            if(empty($messages[$i])) {
                                $messages[$i] = '';
                            }
                            
                            if((\strlen($messages[$i]) + \strlen($part) + 2) >= $split['maxLength']) {
                                $i++;
                                $messages[$i] = '';
                            }
                            
                            $messages[$i] .= $part.$split['char'];
                        }
                        
                        $promise = \React\Promise\resolve();
                        foreach($messages as $key => $message) {
                            $promise = $promise->then(function () use ($chunkedSend, &$files, $key, $i, $message, &$msg, $split) {
                                $fs = null;
                                if($files) {
                                    $fs = $files;
                                    $files = nulL;
                                }
                                
                                $message = array(
                                    'content' => ($key > 0 ? $split['before'] : '').$message.($key < $i ? $split['after'] : '')
                                );
                                
                                if(!empty($msg['embed'])) {
                                    $message['embed'] = $msg['embed'];
                                    $msg['embed'] = null;
                                }
                                
                                return $chunkedSend($message, $fs);
                            }, $reject);
                        }
                        
                        return $promise->then(function () use ($collection, $resolve) {
                            $resolve($collection);
                        }, $reject);
                    }
                }
                
                $this->client->apimanager()->endpoints->channel->createMessage($this->id, $msg, ($files ?? array()))->then(function ($response) use ($resolve) {
                    $resolve($this->_createMessage($response));
                }, $reject);
            }, $reject);
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
    
    function isTyping(\CharlotteDunois\Yasmin\Models\User $user) {
        return $this->typings->get($user->id);
    }
    
    function isTypingSince(\CharlotteDunois\Yasmin\Models\User $user) {
        if($this->isTyping($user) === false) {
            return -1;
        }
        
        return (\time() - $this->typings->get($user->id)['timestamp']);
    }
    
    function _createMessage(array $message) {
        if($this->messages->has($message['id'])) {
            return $this->messages->get($message['id']);
        }
        
        $msg = new \CharlotteDunois\Yasmin\Models\Message($this->client, $this, $message);
        $this->messages->set($msg->id, $msg);
        return $msg;
    }
    
    function _updateTyping(\CharlotteDunois\Yasmin\Models\User $user, int $timestamp = null) {
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
