<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class User extends Structure
    implements \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface { //TODO: Implementation
    
    protected $id;
    protected $username;
    protected $discriminator;
    protected $bot;
    protected $avatar;
    protected $email;
    protected $verified;
    protected $tag;
    
    protected $createdTimestamp;
    
    /**
     * @access private
     */
    function __construct($client, $user) {
        parent::__construct($client);
        
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->discriminator = $user['discriminator'];
        $this->bot = (!empty($user['bot']));
        $this->avatar = $user['avatar'];
        $this->email = (!empty($user['email']) ? $user['email'] : '');
        $this->verified = (!empty($user['verified']));
        
        $this->tag = $this->username.'#'.$this->discriminator;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->getTimestamp();
    }
    
    /**
     * @property-read string                                               $id                 The user ID.
     * @property-read string                                               $username           The username.
     * @property-read string                                               $discriminator      The discriminator of this user.
     * @property-read boolean                                              $bot                Is the user a bot? Or are you a bot?
     * @property-read string                                               $avatar             The hash of the user's avatar.
     * @property-read string                                               $email              An email address or maybe nothing at all. More likely to be nothing at all.
     * @property-read boolean                                              $verified           I wonder if the user is verified.
     * @property-read string                                               $tag                Username#Discriminator.
     * @property-read int                                                  $createdTimestamp   The timestamp of when this user was created.
     *
     * @property-read \DateTime                                            $createdAt          An DateTime object of the createdTimestamp.
     * @property-read int                                                  $defaultAvatar      The identifier of the default avatar for this user.
     * @property-read \CharlotteDunois\Yasmin\Structures\DMChannel|null    $dmChannel          The DM channel for this user, if it exists.
     * @property-read string|null                                          $notes              The notes of the Client User for this user. (User Accounts only)
     * @property-read \CharlotteDunois\Yasmin\Structures\Presence|null     $presence           The presence for this usre.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return (new \DateTime('@'.$this->createdTimestamp));
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
            break;
            case 'notes': //TODO: User Account only
                if($this->client->getClientUser()->notes->has($this->id)) {
                    $this->client->getClientUser()->notes->get($this->id);
                }
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
            break;
        }
        
        return null;
    }
    
    /**
     * Automatically converts the User object to a mention.
     */
    function __toString() {
        return '<@'.$this->id.'>';
    }
    
    /**
     * Opens a DM channel to this user.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Structures\DMChannel|null>
     */
    function createDM() { //TODO
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if($channel) {
                return $resolve($channel);
            }
        });
    }
    
    /**
     * Deletes an existing DM channel to this user.
     * @return \React\Promise\Promise<null>
     */
    function deleteDM() { //TODO
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if(!$channel) {
                return $resolve();
            }
        });
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
     * Get the userprofile of this user. (User Accounts only)
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Structures\Userprofile>
     */
    function fetchProfile() {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    /**
     * Set notes for this user. (User Accounts only)
     * @return \React\Promise\Promise<null>
     */
    function setNote(string $note) { //TODO: User Account only
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function acknowledge() {
        $channel = $this->__get('dmChannel');
        if(!$channel) {
            return \React\Promise\resolve();
        }
        
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($channel) {
            
        });
    }
    
    function awaitMessages(callable $filter, array $options = array()) {
        $channel = $this->__get('dmChannel');
        if($channel) {
            $channel = \React\Promise\resolve($channel);
        } else {
            $channel = $this->createDM();
        }
        
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($channel, $filter, $options) {
            return $channel->then(function ($dm) use ($filter, $options, $resolve, $reject) {
                return $dm->awaitMessages($filter, $options)->then($resolve, $reject);
            }, $reject);
        });
    }
    
    function bulkDelete($messages) {
        $channel = $this->__get('dmChannel');
        if(!$channel) {
            return \React\Promise\reject(new \Exception('You can not bulk delete inside a non-existing channel'));
        }
        
        return $channel->bulkDelete($messages);
    }
    
    function search(array $options = array()) {
        $channel = $this->__get('dmChannel');
        if(!$channel) {
            return \React\Promise\reject(new \Exception('You can not bulk delete inside a non-existing channel'));
        }
        
        return $channel->search($options);
    }
    
    function send(string $message, array $options = array()) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($message, $options) {
            $channel = $this->__get('dmChannel');
            if($channel) {
                $channel = \React\Promise\resolve($channel);
            } else {
                $channel = $this->createDM();
            }
            
            $channel->then(function ($channel) use ($message, $options, $resolve, $reject) {
                return $channel->send($message, $options)->then($resolve, $reject);
            }, $reject);
        });
    }
    
    function startTyping() {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if($channel) {
                $channel = \React\Promise\resolve($channel);
            } else {
                $channel = $this->createDM();
            }
            
            $channel->then(function ($channel) use ($resolve, $reject) {
                return $channel->startTyping()->then($resolve, $reject);
            }, $reject);
        });
    }
    
    function stopTyping(bool $force = false) {
        $channel = $this->__get('dmChannel');
        if(!$channel) {
            return \React\Promise\resolve();
        }
        
        return $channel->stopTyping($force);
    }
    
    function typingCount() {
        $channel = $this->__get('dmChannel');
        if(!$channel) {
            return 0;
        }
        
        return $channel->typingCount();
    }
    
    function typingIn($channel) {
        if(!($channel instanceof \CharlotteDunois\Yasmin\Structures\Textchannel) && (is_string($channel) && \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($channel)->isValid() === false)) {
            throw new \Exception('The "channel" argument is neither an instance of Textchannel nor a Snowflake');
        }
        
        $channel = $this->client->channels->resolve($channel);
        return $channel->isTyping($this);
    }
    
    function typingSinceIn($channel) {
        if(!($channel instanceof \CharlotteDunois\Yasmin\Structures\Textchannel) && (is_string($channel) && \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($channel)->isValid() === false)) {
            throw new \Exception('The "channel" argument is neither an instance of Textchannel nor a Snowflake');
        }
        
        $channel = $this->client->channels->resolve($channel);
        return $channel->isTypingSince($this);
    }
    
    private function getAvatarExtension() {
        return (strpos($this->avatar, 'a_') === 0 ? 'gif' : 'webp');
    }
}
