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
 * @see https://discordapp.com/developers/docs/topics/gateway#message-delete-bulk
 * @internal
 */
class MessageDeleteBulk {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $messages = new \CharlotteDunois\Yasmin\Utils\Collection();
            $messagesRaw = array();
            
            foreach($data['ids'] as $id) {
                $message = $channel->messages->get($id);
                if($message) {
                    $channel->messages->delete($message->id);
                    $messages->set($message->id, $message);
                }
                
                $messagesRaw[] = $id;
            }
            
            if($messages->count() > 0) {
                $this->client->emit('messageDeleteBulk', $messages);
            }
            
            $this->client->emit('messageDeleteBulkRaw', $channel, $messagesRaw);
        }
    }
}
