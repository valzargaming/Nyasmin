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
 * @link https://discordapp.com/developers/docs/topics/gateway#message-delete-bulk
 * @access private
 */
class MessageDeleteBulk {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $messages = new \CharlotteDunois\Yasmin\Structures\Collection();
            $messagesRaw = array();
            
            foreach($data['ids'] as $id) {
                $message = $channel->messages->get($id);
                if($message) {
                    $messages->set($id, $message);
                }
                
                $messagesRaw[] = $id;
            }
            
            if($messages->count() > 0) {
                $this->client->emit('messageDeleteBulk', $messages);
            }
            
            $this->client->emit('messageDeleteBulkRaw', $messagesRaw);
        }
    }
}
