<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @link https://discordapp.com/developers/docs/topics/gateway#message-reaction-remove
 * @access private
 */
class MessageReactionRemove {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->messages->get($data['message_id']);
            if($message) {
                $id = (!empty($data['emoji']['id']) ? $data['emoji']['id'] : $data['emoji']['name']);
                
                $reaction = $message->reactions->get($id);
                if($reaction) {
                    $reaction->_decrementCount();
                    
                    if($reaction->count === 0) {
                        $message->reactions->delete($reaction->emoji->id);
                    }
                    
                    $this->client->emit('messageReactionRemove', $reaction);
                }
            }
        }
    }
}
