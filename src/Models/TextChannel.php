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
 * Represents a guild's text channel.
 *
 * @property string                                                                                   $id                     The channel ID.
 * @property string                                                                                   $type                   The channel type. ({@see \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES})
 * @property  \CharlotteDunois\Yasmin\Models\Guild                                                    $guild                  The associated guild.
 * @property int                                                                                      $createdTimestamp       The timestamp of when this channel was created.
 * @property  string                                                                                  $name                   The channel name.
 * @property  string                                                                                  $topic                  The channel topic.
 * @property  bool                                                                                    $nsfw                   Whether the channel is marked as NSFW or not.
 * @property  string|null                                                                             $parentID               The ID of the parent channel, or null.
 * @property  int                                                                                     $position               The channel position.
 * @property \CharlotteDunois\Yasmin\Utils\Collection                                                 $permissionOverwrites   A collection of PermissionOverwrite instances.
 * @property string|null                                                                              $lastMessageID          The last message ID, or null.
 * @property \CharlotteDunois\Yasmin\Models\MessageStorage                                            $messages               The storage with all cached messages.
 *
 * @property \DateTime                                                                                $createdAt              The DateTime instance of createdTimestamp.
 * @property \CharlotteDunois\Yasmin\Models\Message|null                                              $lastMessage            The last message, or null.
 * @property  \CharlotteDunois\Yasmin\Models\CategoryChannel|null                                     $parent                 Returns the channel's parent, or null.
 * @property  bool|null                                                                               $permissionsLocked      If the permissionOverwrites match the parent channel, or null if no parent.
 */
class TextChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface {
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait, \CharlotteDunois\Yasmin\Traits\TextChannelTrait;
    
    protected $guild;
    
    protected $messages;
    protected $typings;
    
    protected $id;
    protected $type;
    protected $parentID;
    protected $name;
    protected $topic;
    protected $nsfw;
    protected $position;
    protected $permissionOverwrites;
    
    protected $createdTimestamp;
    protected $lastMessageID;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->messages = new \CharlotteDunois\Yasmin\Models\MessageStorage($this->client, $this);
        $this->typings = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->id = $channel['id'];
        $this->type = \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES[$channel['type']];
        $this->lastMessageID = $channel['last_message_id'] ?? null;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        $this->permissionOverwrites = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->_patch($channel);
    }
    
    /**
     * @inheritDoc
     *
     * @return bool|null|\DateTime|\CharlotteDunois\Yasmin\Models\CategoryChannel|\CharlotteDunois\Yasmin\Models\Message
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
            case 'parent':
                return $this->guild->channels->get($this->parentID);
            break;
            case 'permissionsLocked':
                $parent = $this->parent;
                if($parent) {
                    if($parent->permissionOverwrites->count() !== $this->permissionOverwrites->count()) {
                        return false;
                    }
                    
                    return !((bool) $this->permissionOverwrites->first(function ($perm) use ($parent) {
                        $permp = $parent->permissionOverwrites->get($perm->id);
                        return (!$permp || $perm->allowed->bitfield !== $permp->allowed->bitfield || $perm->denied->bitfield !== $permp->denied->bitfield);
                    }));
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Create a webhook for the channel. Resolves with the new Webhook instance.
     * @param string       $name
     * @param string|null  $avatar  An URL or file path, or data.
     * @param string       $reason
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Webhook
     */
    function createWebhook(string $name, ?string $avatar = null, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($name, $avatar, $reason) {
            if(!empty($avatar)) {
                $file = \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($avatar)->then(function ($avatar) {
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeBase64URI($avatar);
                });
            } else {
                $file = \React\Promise\resolve('');
            }
            
            $file->then(function ($avatar = null) use ($name, $reason, $resolve, $reject) {
                $this->client->apimanager()->endpoints->webhook->createWebhook($this->id, $name, ($avatar ?? ''), $reason)->then(function ($data) use ($resolve) {
                    $hook = new \CharlotteDunois\Yasmin\Models\Webhook($this->client, $data);
                    $resolve($hook);
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Fetches the channel's webhooks. Resolves with a Collection of Webhook instances, mapped by their ID.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Webhook
     */
    function fetchWebhooks() {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) {
            $this->client->apimanager()->endpoints->webhook->getChannelWebhooks($this->id)->then(function ($data) use ($resolve) {
                $collect = new \CharlotteDunois\Yasmin\Utils\Collection();
                
                foreach($data as $web) {
                    $hook = new \CharlotteDunois\Yasmin\Models\Webhook($this->client, $web);
                    $collect->set($hook->id, $hook);
                }
                
                $resolve($collect);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<#'.$this->id.'>';
    }
    
    /**
     * @internal
     */
    function _patch(array $channel) {
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->topic = $channel['topic'] ?? $this->topic ?? '';
        $this->nsfw = $channel['nsfw'] ?? $this->nsfw ?? false;
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        
        if(isset($channel['permission_overwrites'])) {
            $this->permissionOverwrites->clear();
            
            foreach($channel['permission_overwrites'] as $permission) {
                $overwrite = new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($this->client, $this, $permission);
                $this->permissionOverwrites->set($overwrite->id, $overwrite);
            }
        }
    }
}
