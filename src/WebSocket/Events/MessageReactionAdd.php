<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @see https://discordapp.com/developers/docs/topics/gateway#message-reaction-add
 * @internal
 */
class MessageReactionAdd {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->messages->get($data['message_id']);
            if($message) {
                $reaction = $message->_addReaction($data);
                
                $user = $this->client->users->get($data['user_id']);
                if($user) {
                    $reaction->users->set($user->id, $user);
                }
                
                $this->client->emit('messageReactionAdd', $reaction, $user);
            }
        }
    }
}
