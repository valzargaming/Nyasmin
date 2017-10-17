<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

class ChannelDelete {
    protected $client;
    
    function __construct($client) {
        $this->client = $client;
    }
    
    function handle($data) {
        $channel = $this->client->channels->get($data['id']);
        if($channel) {
            if($channel->guild) {
                $channel->guild->channels->delete($channel->id);
            }
            
            $this->client->channels->delete($channel->id);
        }
    }
}
