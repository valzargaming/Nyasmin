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
 * @access private
 */
class ChannelUpdate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['id']);
        if($channel) {
            $oldChannel = clone $channel;
            $channel->_patch($data);
            
            $this->client->emit('channelUpdate', $channel, $oldChannel);
        }
    }
}
