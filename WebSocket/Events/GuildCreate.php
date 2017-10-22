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
class GuildCreate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = new \CharlotteDunois\Yasmin\Structures\Guild($this->client, $data);
        $this->client->guilds->set($guild->id, $guild);
        
        $this->client->emit('guildCreate', $guild);
    }
}
