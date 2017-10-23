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
 * @link https://discordapp.com/developers/docs/topics/gateway#message-create
 * @access private
 */
class MessageCreate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->_createMessage($data);
            
            if($message->guild && !$message->member && !$message->author->webhook) {
                $message->guild->fetchMember($message->author->id)->then(function () use ($message) {
                    $this->client->emit('message', $message);
                }, function () use ($message) {
                    $this->client->emit('message', $message);
                });
            } else {
                $this->client->emit('message', $message);
            }
        }
    }
}
