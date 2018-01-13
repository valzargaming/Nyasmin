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
 *
 * @property string                                               $id                 The user ID.
 * @property string                                               $username           The username.
 * @property string                                               $discriminator      The discriminator of this user.
 * @property boolean                                              $bot                Is the user a bot? Or are you a bot?
 * @property string                                               $avatar             The hash of the user's avatar.
 * @property string                                               $email              An email address or maybe nothing at all. More likely to be nothing at all.
 * @property boolean|null                                         $mfaEnabled         Whether the user has two factor enabled on their account, or null if no information provided.
 * @property boolean|null                                         $verified           Whether the email on this account has been verified, or null if no information provided.
 * @property boolean                                              $webhook            Determines wether the user is a webhook or not.
 * @property int                                                  $createdTimestamp   The timestamp of when this user was created.
 *
 * @property \DateTime                                            $createdAt          An DateTime instance of the createdTimestamp.
 * @property int                                                  $defaultAvatar      The identifier of the default avatar for this user.
 * @property \CharlotteDunois\Yasmin\Models\DMChannel|null        $dmChannel          The DM channel for this user, if it exists, or null.
 * @property \CharlotteDunois\Yasmin\Models\Message|null          $lastMessage        The laste message the user sent while the client was online, or null.
 * @property \CharlotteDunois\Yasmin\Models\Presence|null         $presence           The presence for this user, or null.
 * @property string                                               $tag                Username#Discriminator.
 */
class User extends ClientBase {
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
        $this->webhook = $isWebhook;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        $this->_patch($user);
    }
    
    /**
     * @inheritDoc
     *
     * @return string|int|null|\DateTime|\CharlotteDunois\Yasmin\Models\DMChannel|\CharlotteDunois\Yasmin\Models\Message|\CharlotteDunois\Yasmin\Models\Presence
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
            case 'defaultAvatar':
                return ($this->discriminator % 5);
            break;
            case 'dmChannel':
                $channel = $this->client->channels->first(function ($channel) {
                    return ($channel->type === 'dm' && $channel->isRecipient($this));
                });
                
                return $channel;
            break;
            case 'lastMessage':
                if($this->lastMessageID !== null) {
                    return $this->client->channels->first(function ($channel) {
                        return $channel->messages->has($this->lastMessageID);
                    });
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
     * Opens a DM channel to this user. Resolves with an instance of DMChannel.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\DMChannel
     */
    function createDM() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if($channel) {
                return $resolve($channel);
            }
            
            $this->client->apimanager()->endpoints->user->createUserDM($this->id)->then(function ($data) use ($resolve) {
                $channel = $this->client->channels->factory($data);
                $resolve($channel);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Deletes an existing DM channel to this user.
     * @return \React\Promise\Promise
     */
    function deleteDM() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $channel = $this->__get('dmChannel');
            if(!$channel) {
                return $resolve();
            }
            
            $this->client->apimanager()->endpoints->channel->deleteChannel($channel->id)->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Get the default Avatar URL.
     * @param int  $size   Any powers of 2.
     * @return string
     */
    function getDefaultAvatarURL($size = 256) {
        return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['defaultavatars'], ($this->discriminator % 5)).(!empty($size) ? '?size='.$size : '');
    }
    
    /**
     * Get the Avatar URL.
     * @param int     $size   Any powers of 2.
     * @param string  $format One of png, webp, jpg or gif (empty = default format).
     * @return string|null
     */
    function getAvatarURL($size = 256, $format = '') {
        if(!$this->avatar) {
            return null;
        }
        
        if(empty($format)) {
            $format = $this->getAvatarExtension();
        }
        
        return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['avatars'], $this->id, $this->avatar, $format).(!empty($size) ? '?size='.$size : '');
    }
    
    /**
     * Get the URL of the displayed avatar.
     * @param int     $size   Any powers of 2.
     * @param string  $format One of png, webp, jpg or gif (empty = default format).
     * @return string
     */
    function getDisplayAvatarURL($size = 256, $format = '') {
        return ($this->avatar ? $this->getAvatarURL($size, $format) : $this->getDefaultAvatarURL($size));
    }
    
    /**
     * Fetches the User's connections. Requires connections scope. Resolves with a Collection of UserConnection instances, mapped by their ID.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\UserConnection
     */
    function fetchUserConnections() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->user->getUserConnections()->then(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                foreach($data as $conn) {
                    $connection = new \CharlotteDunois\Yasmin\Models\UserConnection($this->client, $conn);
                    $collect->set($connection->id, $connection);
                }
                
                $resolve($collect);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Automatically converts the User instance to a mention.
     */
    function __toString() {
        return '<@'.$this->id.'>';
    }
    
    /**
     * @internal
     */
    function _patch(array $user) {
        $this->username = $user['username'];
        $this->discriminator = $user['discriminator'] ?? '0000';
        $this->bot = (!empty($user['bot']));
        $this->avatar = $user['avatar'];
        $this->email = (!empty($user['email']) ? $user['email'] : '');
        $this->mfaEnabled = (isset($user['mfa_enabled']) ? !empty($user['mfa_enabled']) : null);
        $this->verified = (isset($user['verified']) ? !empty($user['verified']) : null);
    }
    
    /**
     * Returns default extension for the avatar.
     * @return string
     */
    protected function getAvatarExtension() {
        return (strpos($this->avatar, 'a_') === 0 ? 'gif' : 'png');
    }
}
