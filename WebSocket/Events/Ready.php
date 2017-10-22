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
 * @link https://discordapp.com/developers/docs/topics/gateway#ready
 * @access private
 */
class Ready {
    protected $client;
    protected $countGuilds = 0;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle($data) {
        $this->client->setClientUser($data['user']);
        $this->client->wsmanager()->setSessionID($data['session_id']);
        
        foreach($data['private_channels'] as $channel) {
            $channel = $this->client->channels->factory($channel);
            $this->client->emit('channelCreate', $channel);
        }
        
        $guilds = \count($data['guilds']);
        $this->client->wsmanager()->on('guildCreate', function () use ($guilds) {
            $this->countGuilds++;
            if($this->countGuilds >= $guilds) {
                $this->client->wsmanager()->emit('ready');
            }
        });
    }
}
