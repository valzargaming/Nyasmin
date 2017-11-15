<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * The text based channel.
 *
 * @property string                                         $id                 The channel ID.
 * @property string                                         $type               The channel type ({@see \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES}).
 * @property string|null                                    $lastMessageID      The last message ID, or null.
 * @property int                                            $createdTimestamp   The timestamp of when this channel was created.
 * @property \CharlotteDunois\Yasmin\Models\MessageStorage  $messages           The storage with all cached messages.
 *
 * @property \DateTime                                      $createdAt          The DateTime object of createdTimestamp.
 * @property \CharlotteDunois\Yasmin\Models\Message|null    $lastMessage        The last message, or null.
 */
class TextBasedChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface {
                    
    protected $messages;
    protected $typings;
    protected $typingTriggered = array(
        'count' => 0,
        'timer' => null
    );
    
    protected $id;
    protected $type;
    protected $lastMessageID;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client);
        
        $this->messages = new \CharlotteDunois\Yasmin\Models\MessageStorage($this->client, $this);
        $this->typings = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES[$channel['type']];
        $this->lastMessageID = $channel['last_message_id'] ?? null;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @inheritDoc
     *
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'lastMessage':
                if(!empty($this->lastMessageID) && $this->messages->has($this->lastMessageID)) {
                    return $this->messages->get($this->lastMessageID);
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Deletes multiple messages at once. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array|int  $messages           A collection or array of Message objects, or the number of messages to delete (2-100).
     * @param string                                              $reason
     * @param bool                                                $filterOldMessages  Automatically filters out too old messages (14 days).
     * @return \React\Promise\Promise
     */
    function bulkDelete($messages, string $reason = '', bool $filterOldMessages = false) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($filterOldMessages, $messages, $reason) {
            if(\is_numeric($messages)) {
                $messages = $this->fetchMessages(array('limit' => (int) $messages));
            } else {
                $messages = \React\Promise\resolve($messages);
            }
            
            $messages->then(function ($messages) use ($filterOldMessages, $reason, $resolve, $reject) {
                if($messages instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                    $messages = $messages->all();
                }
                
                if($filterOldMessages) {
                    $messages = \array_filter($messages, function ($message) {
                        return ((\time() - $message->createdTimestamp) < 1209600);
                    });
                }
                
                $messages = \array_map(function ($message) {
                    return $message->id;
                }, $messages);
                
                if(\count($messages) < 2 || \count($messages) > 100) {
                    return $reject(new \InvalidArgumentException('Can not bulk delete less than 2 or more than 100 messages'));
                }
                
                $this->client->apimanager()->endpoints->channel->bulkDeleteMessages($this->id, $messages, $reason)->then(function ($data) use ($resolve) {
                    $resolve($this);
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Collects messages during a specific duration (and max. amount). Resolves with a Collection of Message instances, mapped by their IDs.
     *
     * Options are as following (all are optional):
     *
     *  array( <br />
     *      'time' => int, (duration, in seconds, default 30) <br />
     *      'max' => int, (max. messages to collect) <br />
     *      'errors' => array, (optional, which failed "conditions" (max not reached in time ("time")) lead to a rejected promise, defaults to []) <br />
     *  )
     *
     * @param callable  $filter   The filter to only collect desired messages.
     * @param array     $options  The collector options.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function collectMessages(callable $filter, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($filter, $options) {
            $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
            $timer = array();
            
            $listener = function ($message) use ($collect, $filter, &$listener, $options, $resolve, &$timer) {
                if($message->channel->id === $this->id && $filter($message)) {
                    $collect->set($message->id, $message);
                    
                    if($collect->count() >= ($options['max'] ?? \INF)) {
                        $this->client->removeListener('message', $listener);
                        if(!empty($timer)) {
                            $this->client->cancelTimer($timer[0]);
                        }
                        
                        $resolve($collect);
                    }
                }
            };
            
            $timer[0] = $this->client->addTimer((int) ($options['time'] ?? 30), function() use ($collect, &$listener, $options, $resolve, $reject) {
                $this->client->removeListener('message', $listener);
                
                if(\in_array('time', (array) ($options['errors'] ?? array())) && $collect->count() < ($options['max'] ?? 0)) {
                    return $reject(new \RangeException('Not reached max messages in specified duration'));
                }
                
                $resolve($collect);
            });
            
            $this->client->on('message', $listener);
        }));
    }
    
    /**
     * Fetches a specific message using the ID. Bot account endpoint only. Resolves with an instance of Message.
     * @param  string  $id
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function fetchMessage(string $id) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($id) {
            $this->client->apimanager()->endpoints->channel->getChannelMessage($this->id, $id)->then(function ($data) use ($resolve) {
                $message = $this->_createMessage($data);
                $resolve($message);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Fetches messages of this channel. Resolves with a Collection of Message instances, mapped by their ID.
     *
     * Options are as following:
     *
     *  array( <br />
     *      'after' => string, (message ID) <br />
     *      'around' => string, (message ID) <br />
     *      'before' => string, (message ID) <br />
     *      'limit' => int, (1-100, defaults to 50) <br />
     *  )
     *
     * @param  array  $options
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function fetchMessages(array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options) {
            $this->client->apimanager()->endpoints->channel->getChannelMessages($this->id, $options)->then(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $m) {
                    $message = $this->_createMessage($m);
                    $collect->set($message->id, $message);
                }
                
                $resolve($collect);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Sends a message to a channel. Resolves with an instance of Message, or a Collection of Message instances, mapped by their ID.
     *
     * Options are as following (all are optional):
     *
     *  array( <br />
     *    'embed' => array|\CharlotteDunois\Yasmin\Models\MessageEmbed, (an (embed) array or an instance of MessageEmbed) <br />
     *    'files' => array, (an array of array('name', 'data' || 'path') (associative) or just plain file contents, file paths or URLs) <br />
     *    'nonce' => string, (a snowflake used for optimistic sending) <br />
     *    'disableEveryone' => bool, (whether @everyone and @here should be replaced with plaintext, defaults to client option disableEveryone (which itself defaults to false)) <br />
     *    'tts' => bool, <br />
     *    'split' => bool|array, (array: array('before', 'after', 'char', 'maxLength') (associative) | before: The string to insert before the split, after: The string to insert after the split, char: The string to split on, maxLength: The max. length of each message) <br />
     *  )
     *
     * @param  string  $content  The message content.
     * @param  array   $options  Any message options.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function send(string $content, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($content, $options) {
            self::resolveMessageOptionsFiles($options)->then(function ($files) use ($content, $options, $resolve, $reject) {
                $msg = array(
                    'content' => $content
                );
                
                if(!empty($options['embed'])) {
                    $msg['embed'] = $options['embed'];
                }
                
                if(!empty($options['none'])) {
                    $msg['nonce'] = $options['nonce'];
                }
                
                $disableEveryone = (isset($options['disableEveryone']) ? ((bool) $options['disableEveryone']) : $this->client->getOption('disableEveryone', false));
                if($disableEveryone) {
                    $msg['content'] = \str_replace(array('@everyone', '@here'), array("@\u{200b}everyone", "@\u{200b}here"), $msg['content']);
                }
                
                if(!empty($options['tts'])) {
                    $msg['tts'] = true;
                }
                
                if(!empty($options['split'])) {
                    $split = array('before' => '', 'after' => '', 'char' => "\n", 'maxLength' => 1950);
                    if(\is_array(($options['split'] ?? null))) {
                        $split = \array_merge($split, $options['split']);
                    }
                    
                    $messages = self::resolveMessageOptionsSplit($msg['content'], $options);
                    if($messages !== null) {
                        $collection = new \CharlotteDunois\Yasmin\Utils\Collection();
                        $i = \count($messages);
                        
                        $chunkedSend = function ($msg, $files = null) use ($collection, $reject) {
                            return $this->client->apimanager()->endpoints->channel->createMessage($this->id, $msg, ($files ?? array()))->then(function ($response) use ($collection) {
                                $msg = $this->_createMessage($response);
                                $collection->set($msg->id, $msg);
                            }, $reject);
                        };
                        
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
                        }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
                    }
                }
                
                $this->client->apimanager()->endpoints->channel->createMessage($this->id, $msg, ($files ?? array()))->then(function ($response) use ($resolve) {
                    $resolve($this->_createMessage($response));
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }, $reject);
        }));
    }
    
    /**
     * Starts sending the typing indicator in this channel. Counts up a triggered typing counter.
     */
    function startTyping() {
        if($this->typingTriggered['count'] === 0) {
            $fn = function () {
                $this->client->apimanager()->endpoints->channel->triggerChannelTyping($this->id)->then(function () {
                    $this->_updateTyping($this->client->user, \time());
                }, function () {
                    $this->_updateTyping($this->client->user);
                    $this->typingTriggered['count'] = 0;
                    
                    if($this->typingTriggered['timer']) {
                        $this->client->cancelTimer($this->typingTriggered['timer']);
                        $this->typingTriggered['timer'] = null;
                    }
                })->done(null, array($this->client, 'handlePromiseRejection'));
            };
            
            $this->typingTriggered['timer'] = $this->client->addPeriodicTimer(7, $fn);
            $fn();
        }
        
        $this->typingTriggered['count']++;
    }
    
    /**
     * Stops sending the typing indicator in this channel. Counts down a triggered typing counter.
     * @param  bool  $force  Reset typing counter and stop sending the indicator.
     */
    function stopTyping(bool $force = false) {
        if($this->typingCount() === 0) {
            return \React\Promise\resolve();
        }
        
        $this->typingTriggered['count']--;
        if($force) {
            $this->typingTriggered['count'] = 0;
        }
        
        if($this->typingTriggered['count'] === 0) {
            if($this->typingTriggered['timer']) {
                $this->client->cancelTimer($this->typingTriggered['timer']);
                $this->typingTriggered['timer'] = null;
            }
        }
    }
    
    /**
     * Returns the amount of user typing in this channel.
     * @return int
     */
    function typingCount() {
        return $this->typings->count();
    }
    
    /**
     * Determines whether the given user is typing in this channel or not.
     * @param \CharlotteDunois\Yasmin\Models\User  $user
     * @return bool
     */
    function isTyping(\CharlotteDunois\Yasmin\Models\User $user) {
        return $this->typings->has($user->id);
    }
    
    /**
     * Determines whether how long the given user has been typing in this channel. Returns -1 if the user is not typing.
     * @param \CharlotteDunois\Yasmin\Models\User  $user
     * @return int
     */
    function isTypingSince(\CharlotteDunois\Yasmin\Models\User $user) {
        if($this->isTyping($user) === false) {
            return -1;
        }
        
        return (\time() - $this->typings->get($user->id)['timestamp']);
    }
    
    /**
     * @param array  $message
     * @return \CharlotteDunois\Yasmin\Models\Message
     * @internal
     */
    function _createMessage(array $message) {
        if($this->messages->has($message['id'])) {
            return $this->messages->get($message['id']);
        }
        
        $msg = new \CharlotteDunois\Yasmin\Models\Message($this->client, $this, $message);
        $this->messages->set($msg->id, $msg);
        return $msg;
    }
    
    /**
     * @param \CharlotteDunois\Yasmin\Models\User  $user
     * @param int                                  $timestamp
     * @internal
     */
    function _updateTyping(\CharlotteDunois\Yasmin\Models\User $user, int $timestamp = null) {
        if($timestamp === null) {
            return $this->typings->delete($user->id);
        }
        
        $typing = $this->typings->get($user->id);
        if($typing && $typing['timer'] instanceof \React\EventLoop\Timer\Timer) {
            $this->client->cancelTimer($typing['timer']);
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
    
    /**
     * Resolves files of Message Options.
     * @param array $options
     * @return \React\Promise\Promise
     */
    static function resolveMessageOptionsFiles(array $options) {
        if(empty($options['files'])) {
            return \React\Promise\resolve(array());
        }
        
        $promises = array();
        foreach($options['files'] as $file) {
            if($file instanceof \CharlotteDunois\Yasmin\Models\MessageAttachment) {
                $file = $file->getMessageFilesArray();
            }
            
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
        
        return \React\Promise\all($promises);
    }
    
    /**
     * Resolves split of Message Options. Returns null if no chunks needed.
     * @param string $content
     * @param array $options
     * @return string[]|null
     */
    static function resolveMessageOptionsSplit(string $content, array $options) {
        $split = &$options['split'];
        
        if(\strlen($content) > $split['maxLength']) {
            $i = 0;
            $messages = array();
            
            $parts = \explode($split['char'], $content);
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
            
            return $messages;
        }
        
        return null;
    }
}
