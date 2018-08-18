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
 * Holds message mentions.
 *
 * @property \CharlotteDunois\Yasmin\Models\Message      $message   The message this reaction belongs to.
 * @property \CharlotteDunois\Yasmin\Utils\Collection    $channels  The collection which holds all channel mentions.
 * @property bool                                        $everyone  Whether the message mentions @everyone or @here.
 * @property \CharlotteDunois\Yasmin\Utils\Collection    $members   The collection which holds all members mentions (only in guild channels). Only cached members can be put into this Collection.
 * @property \CharlotteDunois\Yasmin\Utils\Collection    $roles     The collection which holds all roles mentions.
 * @property \CharlotteDunois\Yasmin\Utils\Collection    $users     The collection which holds all users mentions.
 */
class MessageMentions extends ClientBase {
    /**
     * RegEx pattern to match channel mentions.
     * @var string
     */
     const PATTERN_CHANNELS = '/<#(\d+)>/';
     
    /**
     * RegEx pattern to match custom emoji mentions.
     * @var string
     */
    const PATTERN_EMOJIS = '/<a?:(?:.*?):(\d+)>/';
    
    /**
     * RegEx pattern to match role mentions.
     * @var string
     */
    const PATTERN_ROLES = '/<@&(\d+)>/';
    
    /**
     * RegEx pattern to match user mentions.
     * @var string
     */
    const PATTERN_USERS = '/<@!?(\d+)>/';
    
    protected $message;
    
    protected $channels;
    protected $everyone;
    protected $members;
    protected $roles;
    protected $users;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Message $message, array $msg) {
        parent::__construct($client);
        $this->message = $message;
        
        $this->channels = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->members = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->roles = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->users = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->everyone = !empty($msg['mention_everyone']);
        
        \preg_match_all(self::PATTERN_CHANNELS, $message->content, $matches);
        if(!empty($matches[1])) {
            foreach($matches[1] as $match) {
                $channel = $this->client->channels->get($match);
                if($channel) {
                    $this->channels->set($channel->id, $channel);
                }
            }
        }
        
        if(!empty($msg['mentions'])) {
            foreach($msg['mentions'] as $mention) {
                $user = $this->client->users->patch($mention);
                if($user) {
                    $member = null;
                    
                    $this->users->set($user->id, $user);
                    if($message->guild) {
                        $member = $message->guild->members->get($user->id);
                        if($member) {
                            $this->members->set($member->id, $member);
                        }
                    }
                }
            }
        }
        
        if($message->channel->type === 'text' && !empty($msg['mention_roles'])) {
            foreach($msg['mention_roles'] as $id) {
                $role = $message->channel->guild->roles->get($id);
                if($role) {
                    $this->roles->set($role->id, $role);
                }
            }
        }
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
        
        return parent::__get($name);
    }
    
    
}
