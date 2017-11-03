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
 * Represents an user on Discord.
 */
class User extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface { //TODO: Implementation
    
    protected $id;
    protected $username;
    protected $discriminator;
    protected $bot;
    protected $avatar;
    protected $email;
    protected $mfaEnabled;
    protected $verified;
    protected $webhook;
    
    protected $createdTimestamp;
    
    /**
     * The last ID of the message the user sent while the client was online, or null.
     * @var string|null
     */
    public $lastMessageID;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $user, bool $isWebhook = false) {
        parent::__construct($client);
        
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->discriminator = $user['discriminator'] ?? '0000';
        $this->bot = (!empty($user['bot']));
        $this->avatar = $user['avatar'];
        $this->email = (!empty($user['email']) ? $user['email'] : '');
        $this->mfaEnabled = (isset($user['mfa_enabled']) ? !empty($user['mfa_enabled']) : null);
        $this->verified = (isset($user['verified']) ? !empty($user['verified']) : null);
        $this->webhook = $isWebhook;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @property-read string                                               $id                 The user ID.
     * @property-read string                                               $username           The username.
     * @property-read string                                               $discriminator      The discriminator of this user.
     * @property-read boolean                                              $bot                Is the user a bot? Or are you a bot?
     * @property-read string                                               $avatar             The hash of the user's avatar.
     * @property-read string                                               $email              An email address or maybe nothing at all. More likely to be nothing at all.
     * @property-read boolean|null                                         $mfaEnabled         Whether the user has two factor enabled on their account.
     * @property-read boolean|null                                         $verified           Whether the email on this account has been verified.
     * @property-read boolean                                              $webhook            Determines wether the user is a webhook or not.
     * @property-read int                                                  $createdTimestamp   The timestamp of when this user was created.
     *
     * @property-read \DateTime                                            $createdAt          An DateTime object of the createdTimestamp.
     * @property-read int                                                  $defaultAvatar      The identifier of the default avatar for this user.
     * @property-read \CharlotteDunois\Yasmin\Models\DMChannel|null        $dmChannel          The DM channel for this user, if it exists.
     * @property-read \CharlotteDunois\Yasmin\Models\Message|null          $lastMessage        The laste message the user sent while the client was online, or null.
     * @property-read string|null                                          $notes              The notes of the Client User for this user. (User Accounts only)
     * @property-read \CharlotteDunois\Yasmin\Models\Presence|null         $presence           The presence for this user.
     * @property-read string                                               $tag                Username#Discriminator.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'defaultAvatar':
                return ($this->discriminator % 5);
            break;
            case 'dmChannel':
                $channel = $this->client->channels->first(function ($channel) {
                    return ($channel->type === 'dm' && $channel->isRecipient($this));
                });
                
                if($channel) {
                    return $channel;
                }
                
                return null;
            break;
            case 'lastMessage':
                if($this->lastMessageID !== null) {
                    return $this->client->channels->first(function ($channel) {
                        return $channel->messages->has($this->lastMessageID);
                    });
                }
                
                return null;
            break;
            case 'notes': //TODO: User Account only
                if($this->client->user->notes->has($this->id)) {
                    $this->client->user->notes->get($this->id);
                }
                
                return null;
            break;
            case 'presence':
                if($this->client->presences->has($this->id)) {
                    return $this->client->presences->get($this->id);
                }
                
                $guilds = $this->client->guilds->all();
                foreach($guilds as $guild) {
                    if($guild->presences->has($this->id)) {
                        $presence = $guild->presences->get($this->id);
                        $this->client->presences->set($this->id, $presence);
                        
                        return $presence;
                    }
                }
                
                return null;
            break;
            case 'tag':
                return $this->username.'#'.$this->discriminator;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Automatically converts the User object to a mention.
     */
    function __toString() {
        return '<@'.$this->id.'>';
    }
    
    /**
     * Opens a DM channel to this user.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Models\DMChannel>
     */
    function createDM() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if($channel) {
                return $resolve($channel);
            }
            
            $this->client->apimanager()->endpoints->user->createUserDM($this->user->id)->then(function ($data) use ($resolve) {
                $channel = $this->client->channels->factory($data);
                $resolve($channel);
            }, $reject);
        }));
    }
    
    /**
     * Deletes an existing DM channel to this user.
     * @return \React\Promise\Promise<void>
     */
    function deleteDM() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if(!$channel) {
                return $resolve();
            }
            
            $this->client->apimanager()->endpoints->channel->deleteChannel($channel->id)->then($resolve, $reject);
        }));
    }
    
    /**
     * Get the default Avatar URL.
     * @param int    $size   Any powers of 2.
     * @return string
     */
    function getDefaultAvatarURL($size = 256) {
        return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['defaultavatars'], ($this->discriminator % 5)).'?size='.$size;
    }
    
    /**
     * Get the Avatar URL.
     * @param int    $size   Any powers of 2.
     * @param string $format Any image format (empty = default format).
     * @return string|null
     */
    function getAvatarURL($size = 256, $format = '') {
        if(!$this->avatar) {
            return null;
        }
        
        if(empty($format)) {
            $format = $this->getAvatarExtension();
        }
        
        return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['avatars'], $this->id, $this->avatar, $format).'?size='.$size;
    }
    
    /**
     * Get the URL of the displayed avatar.
     * @param int    $size   Any powers of 2.
     * @param string $format Any image format (empty = default format).
     * @return string
     */
    function getDisplayAvatarURL($size = 256, $format = '') {
        return ($this->avatar ? $this->getAvatarURL($size, $format) : $this->getDefaultAvatarURL($size));
    }
    
    /**
     * Fetches the User's connections. Requires connections scope.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Utils\Collection<array>>
     * @todo Make UserConnection object.
     */
    function fetchUserConnections() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->user->getUserConnections()->then(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                foreach($data as $conn) {
                    $collect->set($conn['id'], $conn);
                }
                
                $resolve($collect);
            }, $reject);
        }));
    }
    
    /**
     * Deletes multiple messages at once.
     * @see \CharlotteDunois\Yasmin\Models\TextBasedChannel::bulkDelete
     * @return \React\Promise\Promise<this>
     */
    function bulkDelete($messages, string $reason = '') {
        return $this->createDM()->then(function ($channel) use ($messages, $reason) {
            $channel->bulkDelete($messages, $reason);
        });
    }
    
    /**
     * Collects messages during a specific duration (and max. amount).
     * @see \CharlotteDunois\Yasmin\Models\TextBasedChannel::collectMessages
     * @return \React\Promise\Promise<void>
     */
    function collectMessages(callable $filter, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($filter, $options) {
            $this->createDM()->then(function ($dm) use ($filter, $options, $resolve, $reject) {
                return $dm->awaitMessages($filter, $options)->then($resolve, $reject);
            }, $reject);
        }));
    }
    
    /**
     * Sends a message to a channel.
     * @see \CharlotteDunois\Yasmin\Models\TextBasedChannel::send
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Models\Message>
     */
    function send(string $message, array $options = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($message, $options) {
            $this->createDM()->then(function ($channel) use ($message, $options, $resolve, $reject) {
                return $channel->send($message, $options)->then($resolve, $reject);
            }, $reject);
        }));
    }
    
    /**
     * Starts sending the typing indicator in this channel. Counts up a triggered typing counter.
     */
    function startTyping() {
        $this->createDM()->then(function ($channel) {
            return $channel->startTyping();
        });
    }
    
    /**
     * Stops sending the typing indicator in this channel. Counts down a triggered typing counter.
     * @param  bool  $force  Reset typing counter and stop sending the indicator.
     */
    function stopTyping(bool $force = false) {
        $this->createDM()->then(function ($channel) {
            $channel->stopTyping($force);
        });
    }
    
    /**
     * Returns the amount of user typing in the DM channel.
     * @return int
     */
    function typingCount() {
        $channel = $this->__get('dmChannel');
        if(!$channel) {
            return 0;
        }
        
        return $channel->typingCount();
    }
    
    /**
     * Determines whether the user is typing in the given channel or not.
     * @param \CharlotteDunois\Yasmin\Models\User  $user
     * @return bool
     * @throws \InvalidArgumentException
     */
    function typingIn($channel) {
        $channel = $this->client->channels->resolve($channel);
        return $channel->isTyping($this);
    }
    
    /**
     * Determines whether how long the user has been typing in the given channel. Returns -1 if the user is not typing.
     * @param \CharlotteDunois\Yasmin\Models\User  $user
     * @return int
     * @throws \InvalidArgumentException
     */
    function typingSinceIn($channel) {
        $channel = $this->client->channels->resolve($channel);
        return $channel->isTypingSince($this);
    }
    
    protected function getAvatarExtension() {
        return (strpos($this->avatar, 'a_') === 0 ? 'gif' : 'webp');
    }
}
