<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a message.
 *
 * @property string                                                                                      $id                 The message ID.
 * @property \CharlotteDunois\Yasmin\Models\User                                                         $author             The user that created the message.
 * @property \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface                                     $channel            The channel this message was created in.
 * @property int                                                                                         $createdTimestamp   The timestamp of when this message was created.
 * @property int|null                                                                                    $editedTimestamp    The timestamp of when this message was edited, or null.
 * @property string                                                                                      $content            The message content.
 * @property string                                                                                      $cleanContent       The message content with all mentions replaced.
 * @property \CharlotteDunois\Yasmin\Utils\Collection                                                    $attachments        A collection of attachments in the message - mapped by their ID. ({@see \CharlotteDunois\Yasmin\Models\MessageAttachment})
 * @property \CharlotteDunois\Yasmin\Models\MessageEmbed[]                                               $embeds             An array of embeds in the message.
 * @property \CharlotteDunois\Yasmin\Models\MessageMentions                                              $mentions           All valid mentions that the message contains.
 * @property bool                                                                                        $tts                Whether or not the message was Text-To-Speech.
 * @property string|null                                                                                 $nonce              A random number or string used for checking message delivery, or null.
 * @property bool                                                                                        $pinned             Whether the message is pinned or not.
 * @property bool                                                                                        $system             Whether the message is a system message.
 * @property string                                                                                      $type               The type of the message. ({@see \CharlotteDunois\Yasmin\Constants::MESSAGE_TYPES})
 * @property \CharlotteDunois\Yasmin\Utils\Collection                                                    $reactions          A collection of message reactions, mapped by ID (or name). ({@see \CharlotteDunois\Yasmin\Models\MessageReaction})
 * @property string|null                                                                                 $webhookID          ID of the webhook that sent the message, if applicable, or null.
 *
 * @property \DateTime                                                                                   $createdAt          An DateTime instance of the createdTimestamp.
 * @property \DateTime|null                                                                              $editedAt           An DateTime instance of the editedTimestamp, or null.
 * @property bool                                                                                        $deletable          Whether the client user can delete the message.
 * @property bool                                                                                        $editable           Whether the client user can edit the message.
 * @property bool                                                                                        $pinnable           Whether the client user can pin the message.
 * @property \CharlotteDunois\Yasmin\Models\Guild|null                                                   $guild              The correspondending guild (if message posted in a guild), or null.
 * @property \CharlotteDunois\Yasmin\Models\GuildMember|null                                             $member             The correspondending guildmember of the author (if message posted in a guild), or null.
 */
class Message extends ClientBase {
    /**
     * The string used in Message::reply to separate the mention and the content.
     * @var string
     */
    static public $replySeparator = ' ';
    
    protected $id;
    protected $author;
    protected $channel;
    protected $content;
    protected $createdTimestamp;
    protected $editedTimestamp;
    protected $tts;
    protected $nonce;
    protected $pinned;
    protected $system;
    protected $type;
    protected $webhookID;
    
    protected $attachments;
    protected $cleanContent;
    protected $embeds = array();
    protected $mentions;
    protected $reactions;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, array $message) {
        parent::__construct($client);
        $this->channel = $channel;
        
        $this->id = $message['id'];
        $this->author = (empty($message['webhook_id']) ? $this->client->users->patch($message['author']) : new \CharlotteDunois\Yasmin\Models\User($this->client, $message['author'], true));
        
        $this->author->lastMessageID = $this->id;
        $this->createdTimestamp = (new \DateTime($message['timestamp']))->getTimestamp();
        
        $this->attachments = new \CharlotteDunois\Yasmin\Utils\Collection();
        foreach($message['attachments'] as $attachment) {
            $atm = new \CharlotteDunois\Yasmin\Models\MessageAttachment($attachment);
            $this->attachments->set($atm->id, $atm);
        }
        
        $this->reactions = new \CharlotteDunois\Yasmin\Utils\Collection();
        if(!empty($message['reactions'])) {
            foreach($message['reactions'] as $reaction) {
                $emoji = ($this->client->emojis->get($reaction['emoji']['id'] ?? $reaction['emoji']['name']) ?? (new \CharlotteDunois\Yasmin\Models\Emoji($this->client, $this->channel->guild, $reaction['emoji'])));
                $this->reactions->set(($emoji->id ?? $emoji->name), (new \CharlotteDunois\Yasmin\Models\MessageReaction($this->client, $this, $emoji, $reaction)));
            }
        }
        
        $this->_patch($message);
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
            case 'editedAt':
                if($this->editedTimestamp !== null) {
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->editedTimestamp);
                }
                
                return null;
            break;
            case 'deletable':
            case 'pinnable':
                if($this->channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
                    $member = $this->channel->guild->members->get($this->author->id);
                    if($member) {
                        return $member->permissionsIn($this->channel)->has(\CharlotteDunois\Yasmin\Constants::PERMISSIONS['MANAGE_MESSAGES']);
                    }
                }
                
                return false;
            break;
            case 'editable':
                return ($this->author->id === $this->client->user->id);
            break;
            case 'guild':
                if($this->channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
                    return $this->channel->guild;
                }
                
                return null;
            break;
            case 'member':
                if($this->channel->guild) {
                    return $this->channel->guild->members->get($this->author->id);
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Removes all reactions from the message. Resolves with $this.
     * @return \React\Promise\Promise
     */
    function clearReactions() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->deleteMessageReactions($this->channel->id, $this->id)->then(function () use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Collects reactions during a specific duration. Resolves with a Collection of MessageReaction instances, mapped by their IDs or names (unicode emojis).
     *
     * Options are as following:
     *
     * <pre>
     * array(
     *   'max' => int, (max. message reactions to collect)
     *   'time' => int, (duration, in seconds, default 30)
     *   'errors' => array, (optional, which failed "conditions" (max not reached in time ("time")) lead to a rejected promise, defaults to [])
     * )
     * </pre>
     *
     * @param callable  $filter   The filter to only collect desired reactions.
     * @param array     $options  The collector options.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\MessageReaction
     *
     */
    function collectReactions(callable $filter, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($filter, $options) {
            $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
            $timer = null;
            
            $listener = function ($reaction) use (&$collect, $filter, &$listener, $options, $resolve, &$timer) {
                if($this->id === $reaction->message->id && $filter($reaction)) {
                    $collect->set(($reaction->emoji->id ?? $reaction->emoji->name), $reaction);
                    
                    if($collect->count() >= ($options['max'] ?? \INF)) {
                        $this->client->removeListener('messageReactionAdd', $listener);
                        if($timer !== null) {
                            $this->client->cancelTimer($timer);
                        }
                        
                        $resolve($collect);
                    }
                }
            };
            
            $timer = $this->client->addTimer((int) ($options['time'] ?? 30), function() use (&$collect, &$listener, $options, $resolve, $reject) {
                $this->client->removeListener('messageReactionAdd', $listener);
                
                if(\in_array('time', (array) ($options['errors'] ?? array())) && $collect->count() < ($options['max'] ?? 0)) {
                    return $reject(new \RangeException('Not reached max message reactions in specified duration'));
                }
                
                $resolve($collect);
            });
            
            $this->client->on('messageReactionAdd', $listener);
        }));
    }
    
    /**
     * Edits the message. You need to be the author of the message. Resolves with $this.
     * @param string|null  $content  The message contents.
     * @param array        $options  An array with options. Only embed is supported by edit.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Traits\TextChannelTrait::send()
     */
    function edit(?string $content, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($content, $options) {
            $msg = array();
            
            if($content !== null) {
                $msg['content'] = $content;
            }
            
            if(\array_key_exists('embed', $options)) {
                $msg['embed'] = $options['embed'];
            }
            
            $this->client->apimanager()->endpoints->channel->editMessage($this->channel->id, $this->id, $msg)->then(function () use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Deletes the message.
     * @param float|int  $timeout  An integer or float as timeout in seconds, after which the message gets deleted.
     * @param string     $reason
     * @return \React\Promise\Promise
     */
    function delete($timeout = 0, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($timeout, $reason) {
            if($timeout > 0) {
                $this->client->addTimer($timeout, function () use ($reason, $resolve, $reject) {
                    $this->delete(0, $reason)->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
                });
            } else {
                $this->client->apimanager()->endpoints->channel->deleteMessage($this->channel->id, $this->id, $reason)->then(function () use ($resolve) {
                    $resolve();
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }
        }));
    }
    
    /**
     * Fetches the webhook used to create this message. Resolves with an instance of Webhook.
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException
     * @see \CharlotteDunois\Yasmin\Models\Webhook
     */
    function fetchWebhook() {
        if($this->webhookID === null) {
            throw new \BadMethodCallException('Unable to fetch webhook from a message that was not posted by a webhook');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->webhook->getWebhook($this->webhookID)->then(function ($data) use ($resolve) {
                $webhook = new \CharlotteDunois\Yasmin\Models\Webhook($this->client, $data);
                $resolve($webhook);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Pins the message. Resolves with $this.
     * @return \React\Promise\Promise
     */
    function pin() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->pinChannelMessage($this->channel->id, $this->id)->then(function () use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Reacts to the message with the specified unicode or custom emoji. Resolves with an instance of MessageReaction
     * @param \CharlotteDunois\Yasmin\Models\Emoji|\CharlotteDunois\Yasmin\Models\MessageReaction|string  $emoji
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\MessageReaction
     */
    function react($emoji) {
        try {
            $emoji = $this->client->emojis->resolve($emoji);
        } catch(\InvalidArgumentException $e) {
            if(\is_numeric($e)) {
                throw $e;
            }
            
            $match = (bool) \preg_match('/(?:<a?:)?(.+):(\d+)/', $emoji, $matches);
            if($match) {
                $emoji = $matches[1].':'.$matches[2];
            } else {
                $emoji = \rawurlencode($emoji);
            }
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($emoji) {
            if($emoji instanceof \CharlotteDunois\Yasmin\Models\Emoji) {
                $emoji = $emoji->identifier;
            }
            
            $timer = null;
            $listener = function ($reaction) use (&$listener, &$timer, $emoji, $resolve) {
                if($reaction->message->id === $this->id  && $reaction->emoji->identifier === $emoji) {
                    if($timer !== null) {
                        $this->client->cancelTimer($timer);
                    }
                    
                    $this->client->removeListener('messageReactionAdd', $listener);
                    $resolve($reaction);
                }
            };

            $timer = $this->client->addTimer(30, function () use (&$listener, $reject) {
                $this->client->removeListener('messageReactionAdd', $listener);
                $reject(new \Exception('Message Reaction did not arrive in time'));
            });
            
            $this->client->on('messageReactionAdd', $listener);
            $this->client->apimanager()->endpoints->channel->createMessageReaction($this->channel->id, $this->id, $emoji)->otherwise($reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Replies to the message. Resolves with an instance of Message, or with a Collection of Message instances, mapped by their ID.
     * @param string  $content
     * @param array   $options
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Traits\TextChannelTrait::send()
     */
    function reply(string $content, array $options = array()) {
        return $this->channel->send($this->author->__toString().self::$replySeparator.$content, $options);
    }
    
    /**
     * Unpins the message. Resolves with $this.
     * @return \React\Promise\Promise
     */
    function unpin() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->unpinChannelMessage($this->channel->id, $this->id)->then(function () use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Automatically converts to the message content.
     */
    function __toString() {
        return $this->content;
    }
    
    /**
     * @internal
     */
    function _addReaction(array $data) {
        $id = (!empty($data['emoji']['id']) ? $data['emoji']['id'] : $data['emoji']['name']);
        
        $reaction = $this->reactions->get($id);
        if(!$reaction) {
            $emoji = $this->client->emojis->get($id);
            if(!$emoji) {
                $emoji = new \CharlotteDunois\Yasmin\Models\Emoji($this->client, ($this->channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface ? $this->channel->guild : null), $data['emoji']);
                if($this->channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
                    $this->channel->guild->emojis->set($id, $emoji);
                }
            }
            
            $reaction = new \CharlotteDunois\Yasmin\Models\MessageReaction($this->client, $this, $emoji, array(
                'count' => 0,
                'me' => (bool) ($this->client->user->id === $data['user_id']),
                'emoji' => $emoji
            ));
            
            $this->reactions->set($id, $reaction);
        } else {
            $reaction->_patch(array('me' => ($this->client->user->id === $data['user_id'])));
        }
        
        $reaction->_incrementCount();
        return $reaction;
    }
    
    /**
     * @internal
     */
    function _patch(array $message) {
        $this->content = $message['content'] ?? $this->content ?? '';
        $this->editedTimestamp = (!empty($message['edited_timestamp']) ? (new \DateTime($message['edited_timestamp']))->getTimestamp() : $this->editedTimestamp);
        
        $this->tts = $message['tts'] ?? $this->tts;
        $this->nonce = $message['nonce'] ?? null;
        $this->pinned = $message['pinned'] ?? $this->pinned;
        $this->system = (!empty($message['type']) ? ($message['type'] > 0) : $this->system);
        $this->type = (!empty($message['type']) ? \CharlotteDunois\Yasmin\Constants::MESSAGE_TYPES[$message['type']] : $this->type);
        $this->webhookID = $message['webhook_id'] ?? $this->webhookID;
        
        if(isset($message['embeds'])) {
            $this->embeds = array();
            foreach($message['embeds'] as $embed) {
                $this->embeds[] = new \CharlotteDunois\Yasmin\Models\MessageEmbed($embed);
            }
        }
        
        $this->cleanContent = $this->content;
        $this->mentions = new \CharlotteDunois\Yasmin\Models\MessageMentions($this->client, $this, $message);
        
        foreach($this->mentions->channels as $channel) {
            $this->cleanContent = \str_replace($channel->__toString(), $channel->name, $this->cleanContent);
        }
        
        foreach($this->mentions->roles as $role) {
            $this->cleanContent = \str_replace($role->__toString(), $role->name, $this->cleanContent);
        }
        
        foreach($this->mentions->users as $user) {
            $this->cleanContent = \str_replace($user->__toString(), ($this->channel->type === 'text' && $this->channel->guild->members->has($user->id) ? $this->channel->guild->members->get($user->id)->displayName : $user->username), $this->cleanContent);
        }
    }
}
