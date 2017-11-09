<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;
use PHPStan\Type\NullType;

/**
 * Represents a webhook.
 * @todo Implementation
 */
class Webhook extends ClientBase {
    protected $id;
    protected $name;
    protected $avatar;
    protected $channelID;
    protected $guildID;
    protected $owner;
    protected $token;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $webhook) {
        parent::__construct($client);
        
        $this->id = $webhook['id'];
        $this->_patch($webhook);
    }
    
    /**
     * @inheritDoc
     *
     * @property-read string                                    $id         The webhook ID.
     * @property-read string|null                               $name       The webhook default name, or null.
     * @property-read string|null                               $avatar     The webhook default avatar, or null.
     * @property-read string|null                               $channelID  The channel the webhook belongs to.
     * @property-read string|null                               $guildID    The guild the webhook belongs to, or null.
     * @property-read \CharlotteDunois\Yasmin\Models\User|null  $owner      The owner of the webhook, or null.
     * @property-read string|null                               $token      The webhook token.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Edits the webhook. Options are as following (at least one is required):
     *
     *  array(
     *    'name' => string,
     *    'avatar' => string, (data, filepath or URL)
     *    'channel' => \CharlotteDunois\Yasmin\Models\TextChannel|string
     *  )
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \InvalidArgumentException
     */
    function edit(array $options, string $reason = '') {
        $data = array();
        
        if(!empty($options['name'])) {
            $data['name'] = $options['name'];
        }
        
        if(!empty($options['channel'])) {
            $data['channel'] = $this->client->channels->resolve($options['channel']);
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($data, $options, $reason) {
            \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($options['avatar'])->then(function ($avatar = null) use ($data, $reason, $resolve, $reject) {
                if(!empty($avatar)) {
                    $data['avatar'] = \CharlotteDunois\Yasmin\Utils\DataHelpers::makeBase64URI($data);
                }
                
                $this->client->apimanager()->endpoints->webhook->modifyWebhook($this->id, $data, $reason)->then(function ($data) use ($resolve) {
                    $this->_patch($data);
                    $resolve($this);
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Deletes the webhook.
     * @param string  $reason
     * @return \React\Promise\Promise<void>
     */
    function delete(string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->webhook->deleteWebhook($this->id, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Executes the webhooks and sends a message to the channel. Options are as following (all are optional):
     *
     *  array(
     *    'embeds' => array<\CharlotteDunois\Yasmin\Models\MessageEmbed|array>, (an array of (embed) array or instance of MessageEmbed)
     *    'files' => array, (an array of array('name', 'data' || 'path') (associative) or just plain file contents, file paths or URLs)
     *    'nonce' => string, (a snowflake used for optimistic sending)
     *    'disableEveryone' => bool, (whether @everyone and @here should be replaced with plaintext, defaults to client option disableEveryone (which itself defaults to false))
     *    'tts' => bool,
     *    'split' => bool|array, (array: array('before', 'after', 'char', 'maxLength') (associative) | before: The string to insert before the split, after: The string to insert after the split, char: The string to split on, maxLength: The max. length of each message)
     *  )
     *
     * @param string  $content  The webhook message content.
     * @param array   $options  Any webhook message options.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Models\Message|\CharlotteDunois\Yasmin\Utils\Collection<\CharlotteDunois\Yasmin\Models\Message>>
     */
    function send(string $content, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($content, $options) {
            \CharlotteDunois\Yasmin\Models\TextBasedChannel::resolveMessageOptionsFiles($options)->then(function ($files) use ($content, $options, $resolve, $reject) {
                $msg = array(
                    'content' => $content
                );
                
                if(!empty($options['embeds'])) {
                    $msg['embeds'] = $options['embeds'];
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
                    if(\is_array($options['split'])) {
                        $split = \array_merge($split, $options['split']);
                    }
                    
                    if(\strlen($msg['content']) > $split['maxLength']) {
                        $collection = new \CharlotteDunois\Yasmin\Utils\Collection();
                        
                        $chunkedSend = function ($msg, $files = null) use ($collection, $reject) {
                            return $this->executeWebhook($msg, ($files ?? array()))->then($resolve, $reject)->then(function ($message) use ($collection) {
                                $collection->set($message->id, $message);
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
                                
                                if(!empty($msg['embeds'])) {
                                    $message['embeds'] = $msg['embeds'];
                                    $msg['embeds'] = null;
                                }
                                
                                return $chunkedSend($message, $fs);
                            }, $reject);
                        }
                        
                        return $promise->then(function () use ($collection, $resolve) {
                            $resolve($collection);
                        }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
                    }
                }
                
                $this->executeWebhook($msg, ($files ?? array()))->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            });
        }));
    }
    
    /**
     * Executes the webhook effectively.
     * @param array  $opts
     * @param array  $files
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Models\Message>
     * @internal
     */
    protected function executeWebhook(array $opts, array $files) {
        return $this->client->apimanager()->endpoints->webhook->executeWebhook($this->id, $this->token, $opts, $files, array('wait' => true))->then(function ($data) use ($resolve) {
            $channel = $this->client->channels->get($this->channelID);
            return $channel->_createMessage($data);
        }, $reject);
    }
    
    /**
     * @internal
     */
    function _patch(array $webhook) {
        $this->name = $webhook['name'] ?? null;
        $this->avatar = $webhook['avatar'] ?? null;
        $this->channelID = $webhook['channel_id'] ?? null;
        $this->guildID = $webhook['guild_id'] ?? null;
        $this->owner = (!empty($webhook['user']) ? $this->client->users->patch($webhook['user']) : null);
        $this->token = $webhook['token'] ?? null;
    }
}
