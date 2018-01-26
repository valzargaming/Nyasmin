<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Traits;

/**
 * The text based channel trait.
 */
trait TextChannelTrait {
    protected $typingTriggered = array(
        'count' => 0,
        'timer' => null
    );
    
    /**
     * Deletes multiple messages at once. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array|int  $messages           A collection or array of Message instances, or the number of messages to delete (2-100).
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
                    return $reject(new \InvalidArgumentException('Unable to bulk delete less than 2 or more than 100 messages'));
                }
                
                $this->client->apimanager()->endpoints->channel->bulkDeleteMessages($this->id, $messages, $reason)->then(function () use ($resolve) {
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
     * <pre>
     * array(
     *   'max' => int, (max. messages to collect)
     *   'time' => int, (duration, in seconds, default 30)
     *   'errors' => array, (optional, which failed "conditions" (max not reached in time ("time")) lead to a rejected promise, defaults to [])
     * )
     * </pre>
     *
     * @param callable  $filter   The filter to only collect desired messages.
     * @param array     $options  The collector options.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function collectMessages(callable $filter, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($filter, $options) {
            $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
            $timer = null;
            
            $listener = function ($message) use (&$collect, $filter, &$listener, $options, $resolve, &$timer) {
                if($message->channel->id === $this->id && $filter($message)) {
                    $collect->set($message->id, $message);
                    
                    if($collect->count() >= ($options['max'] ?? \INF)) {
                        $this->client->removeListener('message', $listener);
                        if($timer) {
                            $this->client->cancelTimer($timer);
                        }
                        
                        $resolve($collect);
                    }
                }
            };
            
            $timer = $this->client->addTimer((int) ($options['time'] ?? 30), function() use (&$collect, &$listener, $options, $resolve, $reject) {
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
     * Fetches a specific message using the ID. Resolves with an instance of Message.
     * @param string  $id
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
     * <pre>
     * array(
     *   'after' => string, (message ID)
     *   'around' => string, (message ID)
     *   'before' => string, (message ID)
     *   'limit' => int, (1-100, defaults to 50)
     * )
     * </pre>
     *
     * @param array  $options
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
     * <pre>
     * array(
     *    'embed' => array|\CharlotteDunois\Yasmin\Models\MessageEmbed, (an (embed) array/object or an instance of MessageEmbed)
     *    'files' => array, (an array of array('name' => string, 'data' => string || 'path' => string) or just plain file contents, file paths or URLs)
     *    'nonce' => string, (a snowflake used for optimistic sending)
     *    'disableEveryone' => bool, (whether @everyone and @here should be replaced with plaintext, defaults to client option disableEveryone (which itself defaults to false))
     *    'tts' => bool,
     *    'split' => bool|array, (*)
     * )
     *
     *   * array(
     *   *   'before' => string, (The string to insert before the split)
     *   *   'after' => string, (The string to insert after the split)
     *   *   'char' => string, (The string to split on)
     *   *   'maxLength' => int, (The max. length of each message)
     *   * )
     * </pre>
     *
     * @param string  $content  The message content.
     * @param array   $options  Any message options.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function send(string $content, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($content, $options) {
            \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveMessageOptionsFiles($options)->then(function ($files) use ($content, $options, $resolve, $reject) {
                $msg = array(
                    'content' => $content
                );
                
                if(!empty($options['embed'])) {
                    $msg['embed'] = $options['embed'];
                }
                
                if(!empty($options['nonce'])) {
                    $msg['nonce'] = $options['nonce'];
                }
                
                $disableEveryone = (isset($options['disableEveryone']) ? ((bool) $options['disableEveryone']) : $this->client->getOption('disableEveryone', false));
                if($disableEveryone) {
                    $msg['content'] = \str_replace(array('@everyone', '@here'), array("@\u{200b}everyone", "@\u{200b}here"), $msg['content']);
                }
                
                if(!empty($options['tts'])) {
                    $msg['tts'] = true;
                }
                
                if(isset($options['split'])) {
                    $options['split'] = $split = \array_merge(\CharlotteDunois\Yasmin\Utils\DataHelpers::DEFAULT_MESSAGE_SPLIT_OPTIONS, (\is_array($options['split']) ? $options['split'] : array()));
                    $messages = \CharlotteDunois\Yasmin\Utils\DataHelpers::splitMessage($msg['content'], $options['split']);
                    
                    if(\count($messages) > 1) {
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
     * @param bool  $force  Reset typing counter and stop sending the indicator.
     */
    function stopTyping(bool $force = false) {
        if($this->typingTriggered['count'] === 0) {
            return;
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
     * @param int|null                             $timestamp
     * @internal
     */
    function _updateTyping(\CharlotteDunois\Yasmin\Models\User $user, ?int $timestamp = null) {
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
}
