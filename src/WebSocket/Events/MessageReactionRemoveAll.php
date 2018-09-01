<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
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
class MessageReactionRemoveAll implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
    }
    
    function handle(\CharlotteDunois\Yasmin\WebSocket\WSConnection $ws, array $data): void {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->messages->get($data['message_id']);
            if($message) {
                $message = \React\Promise\resolve($message);
            } else {
                $message = $channel->fetchMessage($data['message_id']);
            }
            
            $message->done(function (\CharlotteDunois\Yasmin\Models\Message $message) {
                $message->reactions->clear();
                $this->client->emit('messageReactionRemoveAll', $message);
            }, function () {
                // Don't handle it
            });
        }
    }
}
