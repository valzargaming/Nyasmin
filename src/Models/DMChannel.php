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
 * Represents a classic DM channel.
 *
 * @property string                                         $id                 The channel ID.
 * @property string                                         $type               The channel type. {@see \CharlotteDunois\Yasmin\Constants::CHANNEL_TYPES}
 * @property int                                            $createdTimestamp   The timestamp of when this channel was created.
 * @property  string|null                                   $ownerID            The owner ID of this channel.
 * @property  \CharlotteDunois\Yasmin\Utils\Collection      $recipients         The recipients of this channel.
 * @property string|null                                    $lastMessageID      The last message ID, or null.
 * @property \CharlotteDunois\Yasmin\Models\MessageStorage  $messages           The storage with all cached messages.
 *
 * @property \DateTime                                      $createdAt          The DateTime instance of createdTimestamp.
 * @property \CharlotteDunois\Yasmin\Models\Message|null    $lastMessage        The last message, or null.
 * @property  \CharlotteDunois\Yasmin\Models\User|null      $owner              The owner of this channel, or not.
 */
class DMChannel extends ClientBase
    implements \CharlotteDunois\Yasmin\Interfaces\ChannelInterface,
                \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface {
    use \CharlotteDunois\Yasmin\Traits\TextChannelTrait;
    
    protected $messages;
    protected $typings;
    protected $typingTriggered = array(
        'count' => 0,
        'timer' => null
    );
    
    protected $id;
    protected $type;
    protected $ownerID;
    protected $recipients;
    
    protected $createdTimestamp;
    protected $lastMessageID;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client);
        
        $this->ownerID = $channel['owner_id'] ?? null;
        $this->recipients = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        if(!empty($channel['recipients'])) {
            foreach($channel['recipients'] as $rec) {
                $user = $this->client->users->patch($rec);
                if($user) {
                    $this->recipients->set($user->id, $user);
                }
            }
        }
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
            case 'owner':
                return $this->client->users->get($this->ownerID);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Determines whether a given user is a recipient of this channel.
     * @param \CharlotteDunois\Yasmin\Models\User|string  $user  The User instance or user ID.
     * @return bool
     * @throws \InvalidArgumentException
     */
    function isRecipient($user) {
        $user = $this->client->users->resolve($user);
        return $this->recipients->has($user->id);
    }
}
