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
 */
class DMChannel extends TextBasedChannel {
    protected $ownerID;
    protected $recipients;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client, $channel);
        
        $this->ownerID = $channel['owner_id'] ?? null;
        $this->recipients = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        if(!empty($channel['recipients'])) {
            foreach($channel['recipients'] as $rec) {
                $user = $client->users->patch($rec);
                if($user) {
                    $this->recipients->set($user->id, $user);
                }
            }
        }
    }
    
    /**
     * @inheritdoc
     * @property-read  string|null                                $ownerID      The owner ID of this channel.
     * @property-read  \CharlotteDunois\Yasmin\Utils\Collection  $recipients   The recipients of this channel.
     *
     * @property-read  \CharlotteDunois\Yasmin\Models\User|null   $owner        The owner of this channel, or not.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'owner':
                if($this->client->users->has($this->ownerID)) {
                    return $this->client->users->get($this->ownerID);
                }
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Determines whether a given user is a recipient of this channel.
     * @param \CharlotteDunois\Yasmin\Models\User|string  $user  The user object or user ID.
     * @return bool
     * @throws \InvalidArgumentException
     */
    function isRecipient($user) {
        $user = $this->client->users->resolve($user);
        return $this->recipients->has($user->id);
    }
}
