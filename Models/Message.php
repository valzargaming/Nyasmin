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
 * Represents a message.
 * @todo Implementation
 */
class Message extends ClientBase {
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
            $this->attachments->set($attachment['id'], (new \CharlotteDunois\Yasmin\Models\MessageAttachment($attachment)));
        }
        
        $this->reactions = new \CharlotteDunois\Yasmin\Utils\Collection();
        if(!empty($message['reactions'])) {
            foreach($message['reactions'] as $reaction) {
                $emoji = ($this->client->emojis->get($reaction['emoji']['id'] ?? $reaction['emoji']['name']) ?? (new \CharlotteDunois\Yasmin\Models\Emoji($this->client, $this->channel->guild, $reaction['emoji'])));
                $this->reactions->set($emoji->id, (new \CharlotteDunois\Yasmin\Models\MessageReaction($this->client, $this, $emoji, $reaction)));
            }
        }
        
        $this->_patch($message);
    }
    
    /**
     * @property-read string                                                              $id                 The message ID.
     * @property-read \CharlotteDunois\Yasmin\Models\User                                 $author             The user that created the message.
     * @property-read \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface             $channel            The channel this message was created in.
     * @property-read int                                                                 $createdTimestamp   The timestamp of when this message was created.
     * @property-read int|null                                                            $editedTimestamp    The timestamp of when this message was edited.
     *
     * @property-read \DateTime                                                           $createdAt          An DateTime object of the createdTimestamp.
     * @property-read \DateTime|null                                                      $editedAt           An DateTime object of the editedTimestamp.
     * @property-read \CharlotteDunois\Yasmin\Models\Guild|null                           $guild              The correspondending guild (if message posted in a guild).
     * @property-read \CharlotteDunois\Yasmin\Models\GuildMember|null                     $member             The correspondending guildmember of the author (if message posted in a guild).
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
            case 'editedAt':
                if($this->editedTimestamp !== null) {
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->editedTimestamp);
                }
                
                return null;
            break;
            case 'guild':
                return $this->channel->guild;
            break;
            case 'member':
                if($this->channel->guild) {
                    return $this->channel->guild->members->get($this->author->id);
                }
                
                return null;
            break;
            case 'type':
                return $this->channel->type;
            break;
        }
        
        return parent::__get($name);
    }
    
    function edit(array $data) {
        
    }
    
    /**
     * Deletes the message.
     * @param int     $timeout  An integer timeout in seconds, after which the message gets deleted.
     * @param string  $reason
     * @return \React\Promise\Promise<void>
     */
    function delete(int $timeout = 0, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($timeout, $reason) {
            if($timeout > 0) {
                $this->client->addTimer($timeout, function () use ($reason, $resolve, $reject) {
                    $this->delete(0, $reason)->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
                }, true);
            } else {
                $this->client->apimanager()->endpoints->channel->deleteMessage($this->channel->id, $this->id, $reason)->then(function () use ($resolve) {
                    $resolve();
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }
        }));
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
    
    /**
     * @internal
     */
    function _patch(array $message) {
        $this->content = $message['content'] ?? $this->content ?? '';
        $this->editedTimestamp = (!empty($message['edited_timestamp']) ? (new \DateTime($message['edited_timestamp']))->getTimestamp() : null);
        
        $this->tts = (!empty($message['tts']));
        $this->mentionEveryone = (!empty($message['mention_everyone']));
        $this->nonce = (!empty($message['nonce']) ? $message['nonce'] : null);
        $this->pinned = (!empty($message['pinned']));
        $this->system = (!empty($message['type']) ? ($message['type'] > 0 ? true : false) : $this->system);
        
        if(!empty($message['embeds'])) {
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
            $this->cleanContent = \str_replace($user->__toString(), ($guild ? $member->displayName : $user->username), $this->cleanContent);
        }
    }
}
