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
 * @property string                                                                                      $type               The type of the message. ({@see Message::MESSAGE_TYPES})
 * @property \CharlotteDunois\Yasmin\Utils\Collection                                                    $reactions          A collection of message reactions, mapped by ID (or name). ({@see \CharlotteDunois\Yasmin\Models\MessageReaction})
 * @property string|null                                                                                 $webhookID          ID of the webhook that sent the message, if applicable, or null.
 * @property \CharlotteDunois\Yasmin\Models\MessageActivity|null                                         $activity           The activity attached to this message. Sent with Rich Presence-related chat embeds.
 * @property \CharlotteDunois\Yasmin\Models\MessageApplication|null                                      $application        The application attached to this message. Sent with Rich Presence-related chat embeds.
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
     * Default Message Split Options.
     * @source
     */
    const DEFAULT_SPLIT_OPTIONS = array('before' => '', 'after' => '', 'char' => "\n", 'maxLength' => 1950);
    
    /**
     * Messages Types.
     * @var array
     * @source
     */
    const MESSAGE_TYPES = array(
        0 => 'DEFAULT',
        1 => 'RECIPIENT_ADD',
        2 => 'RECIPIENT_REMOVE',
        3 => 'CALL',
        4 => 'CHANNEL_NAME_CHANGE',
        5 => 'CHANNEL_ICON_CHANGE',
        6 => 'CHANNEL_PINNED_MESSAGE',
        7 => 'GUILD_MEMBER_JOIN'
    );
    
    /**
     * The string used in Message::reply to separate the mention and the content.
     * @var string
     * @source
     */
    public static $replySeparator = ' ';
    
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
    protected $activity;
    protected $application;
    
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
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        
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
     * {@inheritdoc}
     * @return mixed
     * @throws \RuntimeException
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
                        return $member->permissionsIn($this->channel)->has(\CharlotteDunois\Yasmin\Models\Permissions::PERMISSIONS['MANAGE_MESSAGES']);
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
                if($this->channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
                    return $this->channel->guild->members->get($this->author->id);
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Removes all reactions from the message. Resolves with $this.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function clearReactions() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->deleteMessageReactions($this->channel->id, $this->id)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Collects reactions during a specific duration. Resolves with a Collection of `[ $messageReaction, $user ]` arrays, mapped by their IDs or names (unicode emojis).
     *
     * Options are as following:
     *
     * ```
     * array(
     *   'max' => int, (max. message reactions to collect)
     *   'time' => int, (duration, in seconds, default 30)
     *   'errors' => array, (optional, which failed "conditions" (max not reached in time ("time")) lead to a rejected promise, defaults to [])
     * )
     * ```
     *
     * @param callable  $filter   The filter to only collect desired reactions. Signature: `function (MessageReaction $messageReaction, User $user): bool`
     * @param array     $options  The collector options.
     * @return \React\Promise\ExtendedPromiseInterface  This promise is cancellable.
     * @throws \RangeException          The exception the promise gets rejected with, if collecting times out.
     * @throws \OutOfBoundsException    The exception the promise gets rejected with, if the promise gets cancelled.
     * @see \CharlotteDunois\Yasmin\Models\MessageReaction
     * @see \CharlotteDunois\Yasmin\Models\User
     * @see \CharlotteDunois\Yasmin\Utils\Collector
     */
    function collectReactions(callable $filter, array $options = array()) {
        $rhandler = function (\CharlotteDunois\Yasmin\Models\MessageReaction $reaction, \CharlotteDunois\Yasmin\Models\User $user) {
            return array(($reaction->emoji->id ?? $reaction->emoji->name), array($reaction, $user));
        };
        $rfilter = function (\CharlotteDunois\Yasmin\Models\MessageReaction $reaction, \CharlotteDunois\Yasmin\Models\User $user) use ($filter) {
            return ($this->id === $reaction->message->id && $filter($reaction, $user));
        };
        
        $collector = new \CharlotteDunois\Yasmin\Utils\Collector($this->client, 'messageReactionAdd', $rhandler, $rfilter, $options);
        return $collector->collect();
    }
    
    /**
     * Edits the message. You need to be the author of the message. Resolves with $this.
     * @param string|null  $content  The message contents.
     * @param array        $options  An array with options. Only embed is supported by edit.
     * @return \React\Promise\ExtendedPromiseInterface
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
            
            $this->client->apimanager()->endpoints->channel->editMessage($this->channel->id, $this->id, $msg)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Deletes the message.
     * @param float|int  $timeout  An integer or float as timeout in seconds, after which the message gets deleted.
     * @param string     $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function delete($timeout = 0, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($timeout, $reason) {
            if($timeout > 0) {
                $this->client->addTimer($timeout, function () use ($reason, $resolve, $reject) {
                    $this->delete(0, $reason)->done($resolve, $reject);
                });
            } else {
                $this->client->apimanager()->endpoints->channel->deleteMessage($this->channel->id, $this->id, $reason)->done(function () use ($resolve) {
                    $resolve();
                }, $reject);
            }
        }));
    }
    
    /**
     * Fetches the webhook used to create this message. Resolves with an instance of Webhook.
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \BadMethodCallException
     * @see \CharlotteDunois\Yasmin\Models\Webhook
     */
    function fetchWebhook() {
        if($this->webhookID === null) {
            throw new \BadMethodCallException('Unable to fetch webhook from a message that was not posted by a webhook');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->webhook->getWebhook($this->webhookID)->done(function ($data) use ($resolve) {
                $webhook = new \CharlotteDunois\Yasmin\Models\Webhook($this->client, $data);
                $resolve($webhook);
            }, $reject);
        }));
    }
    
    /**
     * Returns the jump to message link for this message.
     * @return string
     */
    function getJumpURL() {
        $guild = ($this->channel->type === 'text' ? $this->guild->id : '@me');
        return 'https://canary.discordapp.com/channels/'.$guild.'/'.$this->channel->id.'/'.$this->id;
    }
    
    /**
     * Pins the message. Resolves with $this.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function pin() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->pinChannelMessage($this->channel->id, $this->id)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Reacts to the message with the specified unicode or custom emoji. Resolves with an instance of MessageReaction.
     * @param \CharlotteDunois\Yasmin\Models\Emoji|\CharlotteDunois\Yasmin\Models\MessageReaction|string  $emoji
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Models\MessageReaction
     */
    function react($emoji) {
        try {
            $emoji = $this->client->emojis->resolve($emoji);
        } catch (\InvalidArgumentException $e) {
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
            
            $filter = function (\CharlotteDunois\Yasmin\Models\MessageReaction $reaction, \CharlotteDunois\Yasmin\Models\User $user) use ($emoji) {
                return ($user->id === $this->client->user->id && $reaction->message->id === $this->id && $reaction->emoji->identifier === $emoji);
            };
            
            $prom = \CharlotteDunois\Yasmin\Utils\DataHelpers::waitForEvent($this->client, 'messageReactionAdd', $filter, array('time' => 30))->then(function ($args) use ($resolve) {
                $resolve($args[0]);
            })->otherwise(function ($error) use ($reject) {
                if($error instanceof \RangeException) {
                    $reject(new \RangeException('Message Reaction did not arrive in time'));
                } elseif(!($error instanceof \OutOfBoundsException)) {
                    $reject($error);
                }
            });
            
            $this->client->apimanager()->endpoints->channel->createMessageReaction($this->channel->id, $this->id, $emoji)->done(null, function ($error) use ($prom, $reject) {
                $prom->cancel();
                $reject($error);
            });
        }));
    }
    
    /**
     * Replies to the message. Resolves with an instance of Message, or with a Collection of Message instances, mapped by their ID.
     * @param string  $content
     * @param array   $options
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \CharlotteDunois\Yasmin\Traits\TextChannelTrait::send()
     */
    function reply(string $content, array $options = array()) {
        return $this->channel->send($this->author->__toString().self::$replySeparator.$content, $options);
    }
    
    /**
     * Unpins the message. Resolves with $this.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function unpin() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->channel->unpinChannelMessage($this->channel->id, $this->id)->done(function () use ($resolve) {
                $resolve($this);
            }, $reject);
        }));
    }
    
    /**
     * Automatically converts to the message content.
     * @return string
     */
    function __toString() {
        return $this->content;
    }
    
    /**
     * @return \CharlotteDunois\Yasmin\Models\MessageReaction
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
                'me' => ((bool) ($this->client->user->id === $data['user_id'])),
                'emoji' => $emoji
            ));
            
            $this->reactions->set($id, $reaction);
        } else {
            $botReacted = (bool) ($this->client->user->id === $data['user_id']);
            if($botReacted && !$reaction->me) {
                $reaction->_patch(array('me' => true));
            }
        }
        
        $reaction->_incrementCount();
        return $reaction;
    }
    
    /**
     * @return void
     * @internal
     */
    function _patch(array $message) {
        $this->content = $message['content'] ?? $this->content ?? '';
        $this->editedTimestamp = (!empty($message['edited_timestamp']) ? (new \DateTime($message['edited_timestamp']))->getTimestamp() : $this->editedTimestamp);
        
        $this->tts = $message['tts'] ?? $this->tts;
        $this->nonce = $message['nonce'] ?? null;
        $this->pinned = $message['pinned'] ?? $this->pinned;
        $this->system = (isset($message['type']) ? ($message['type'] > 0) : $this->system);
        $this->type = (isset($message['type']) ? self::MESSAGE_TYPES[$message['type']] : $this->type);
        $this->webhookID = $message['webhook_id'] ?? $this->webhookID;
        $this->activity = (!empty($message['activity']) ? (new \CharlotteDunois\Yasmin\Models\MessageActivity($this->client, $message['activity'])) : $this->activity);
        $this->application = (!empty($message['application']) ? (new \CharlotteDunois\Yasmin\Models\MessageApplication($this->client, $message['application'])) : $this->application);
        
        if(isset($message['embeds'])) {
            $this->embeds = array();
            foreach($message['embeds'] as $embed) {
                $this->embeds[] = new \CharlotteDunois\Yasmin\Models\MessageEmbed($embed);
            }
        }
        
        $this->cleanContent = $this->content;
        $this->mentions = new \CharlotteDunois\Yasmin\Models\MessageMentions($this->client, $this, $message);
        
        foreach($this->mentions->channels as $channel) {
            $this->cleanContent = \str_replace('<#'.$channel->id.'>', $channel->name, $this->cleanContent);
        }
        
        foreach($this->mentions->roles as $role) {
            $this->cleanContent = \str_replace($role->__toString(), $role->name, $this->cleanContent);
        }
        
        foreach($this->mentions->users as $user) {
            $this->cleanContent = \str_replace($user->__toString(), ($this->channel->type === 'text' && $this->channel->guild->members->has($user->id) ? $this->channel->guild->members->get($user->id)->displayName : $user->username), $this->cleanContent);
        }
        
        if(!empty($message['member']) && !$this->guild->members->has($this->author->id)) {
            $member = $message['member'];
            $member['user'] = $message['author'];
            $this->guild->_addMember($member, true);
        }
    }
}
