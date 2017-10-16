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
        
        $this->createdTimestamp = \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->getTimestamp();
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return (new \DateTime('@'.$this->createdTimestamp));
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
                        return $guild->presences->get($this->id);
                    }
                }
            break;
        }
        
        return null;
    }
    
    function __toString() {
        return '<@'.$this->id.'>';
    }
    
    function createDM() { //TODO
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if($channel) {
                return $resolve($channel);
            }
        });
    }
    
    function deleteDM() { //TODO
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if(!$channel) {
                return $resolve();
            }
        });
    }
    
    function defaultAvatar() {
        return ($this->discriminator % 5);
    }
    
    function getDefaultAvatarURL($size = 256) {
        return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['defaultavatars'], ($this->discriminator % 5)).'?size='.$size;
    }
    
    function getAvatarURL($size = 256, $format = '') {
        if(!$this->avatar) {
            return null;
        }
        
        if(empty($format)) {
            $format = $this->getAvatarExtension();
        }
        
        return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['avatars'], $this->id, $this->avatar, $format).'?size='.$size;
    }
    
    function getDisplayAvatarURL($size = 256, $format = '') {
        return ($this->avatar ? $this->getAvatarURL($format) : $this->getDefaultAvatarURL());
    }
    
    function fetchProfile() { //TODO: User Account only
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function setNote(string $note) { //TODO: User Account only
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            
        });
    }
    
    function acknowledge() {
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
        
        return $channel->then(function ($dm) use ($filter, $options) {
            return $dm->awaitMessages($filter, $options);
        }, $reject);
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
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) {
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
