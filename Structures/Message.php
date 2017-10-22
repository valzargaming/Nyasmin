<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Message extends Structure { //TODO: Implementation
    
    protected $id;
    protected $author;
    protected $channel;
    protected $content;
    protected $createdTimestamp;
    protected $editedTimestamp;
    protected $tts;
    protected $mentionEveryone;
    protected $nonce;
    protected $pinned;
    protected $system;
    
    protected $attachments;
    protected $cleanContent;
    protected $embeds = array();
    protected $mentions;
    protected $reactions;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, array $message) {
        parent::__construct($client);
        
        $this->id = $message['id'];
        $this->author = (empty($message['webhook_id']) ? $client->users->patch($message['author']) : new \CharlotteDunois\Yasmin\Structures\Webhook($client, $message['author']));
        $this->channel = $channel;
        $this->content = $message['content'];
        $this->createdTimestamp = (new \DateTime($message['timestamp']))->format('U');
        $this->editedTimestamp = (!empty($message['edited_timestamp']) ? (new \DateTime($message['edited_timestamp']))->format('U') : null);
        $this->tts = (bool) $message['tts'];
        $this->mentionEveryone = (bool) $message['mention_everyone'];
        $this->nonce = (!empty($message['nonce']) ? $message['nonce'] : null);
        $this->pinned = (bool) $message['pinned'];
        $this->system = ($message['type'] > 0 ? true : false);
        
        $this->attachments = new \CharlotteDunois\Yasmin\Structures\Collection();
        foreach($message['attachments'] as $attachment) {
            $this->attachments->set($attachment['id'], (new \CharlotteDunois\Yasmin\Structures\MessageAttachment($attachment)));
        }
        
        foreach($message['embeds'] as $embed) {
            $this->embeds[] = new \CharlotteDunois\Yasmin\Structures\MessageEmbed($embed);
        }
        
        $this->mentions = array(
            'channels' => (new \CharlotteDunois\Yasmin\Structures\Collection()),
            'members' => (new \CharlotteDunois\Yasmin\Structures\Collection()),
            'roles' => (new \CharlotteDunois\Yasmin\Structures\Collection()),
            'users' => (new \CharlotteDunois\Collect\Collection())
        );
        
        $guild = $channel->guild;
        $this->cleanContent = $this->content;
        
        \preg_match_all('/<#(\d+)>/', $this->content, $matches);
        if(!empty($matches[1])) {
            foreach($matches[1] as $match) {
                $mention = '<#'.$match.'>';
                $channel = $this->client->channels->get($match);
                if($channel) {
                    $this->cleanContent = \str_replace($mention, $channel->name, $this->cleanContent);
                }
            }
        }
        
        foreach($message['mentions'] as $mention) {
            $member = null;
            $user = $this->client->users->patch($mention);
            
            $this->mentions['users']->set($mention['id'], $user);
            if($guild) {
                $member = $guild->members->get($mention['id']);
                $this->mentions['members']->set($mention['id'], $member);
            }
            
            $this->cleanContent = \str_replace($user->__toString(), ($guild ? $member->displayName : $user->username), $this->cleanContent);
        }
        
        
        if($guild) {
            foreach($message['mentions_role'] as $mention) {
                $role = $guild->roles->get($mention['id']);
                $this->mentions['roles']->set($mention['id'], $role);
                
                $this->cleanContent = \str_replace($role->__toString(), $role->name, $this->cleanContent);
            }
        }
    }
    
    /**
     * @property-read string                                                                              $id                 The message ID.
     * @property-read \CharlotteDunois\Yasmin\Structures\User|\CharlotteDunois\Yasmin\Structures\Webhook  $author             The user, or webhook, that created the message.
     * @property-read \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface                             $channel            The channel this message was created in.
     * @property-read \CharlotteDunois\Yasmin\structures\Guild|null                                       $guild              The correspondending guild.
     * @property-read int                                                                                 $createdTimestamp   The timestamp of when this message was created.
     *
     * @property-read \DateTime                                            $createdAt          An DateTime object of the createdTimestamp.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return (new \DateTime('@'.$this->createdTimestamp));
            break;
            case 'guild':
                return $this->channel->guild;
            break;
        }
        
        return null;
    }
    
    function edit(array $data) {
        
    }
    
    function delete(string $reason) {
        
    }
    
    function setName(string $name) {
        
    }
    
    function addRestrictedRoles(...$roles) {
        
    }
    
    function removeRestrictedRoles(...$roles) {
        
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        if($this->requireColons === false) {
            return $this->name;
        }
        
        return '<:'.$this->name.':'.$this->id.'>';
    }
}
