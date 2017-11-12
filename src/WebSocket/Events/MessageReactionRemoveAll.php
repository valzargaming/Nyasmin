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
 * @see https://discordapp.com/developers/docs/topics/gateway#message-reaction-remove-all
 * @internal
 */
class MessageReactionRemoveAll {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->messages->get($data['message_id']);
            if($message) {
                foreach($message->reactions as &$reaction) {
                    unset($reaction);
                }
                
                $message->reactions->clear();
                $this->client->emit('messageReactionRemoveAll', $message);
            }
        }
    }
}
