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
 * @see https://discordapp.com/developers/docs/topics/gateway#ready
 * @internal
 */
class Ready {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle($data) {
        $this->client->setClientUser($data['user']);
        $this->client->wsmanager()->setSessionID($data['session_id']);
        
        foreach($data['private_channels'] as $channel) {
            if(!$this->client->channels->has($channel['id'])) {
                $channel = $this->client->channels->factory($channel);
                $this->client->emit('channelCreate', $channel);
            }
        }
        
        foreach($data['guilds'] as $guild) {
            if(!$this->client->guilds->has($guild['id'])) {
                $guild = new \CharlotteDunois\Yasmin\Models\Guild($this->client, $guild);
                $this->client->guilds->set($guild->id, $guild);
            }
        }
        
        $unavailableGuilds = 0;
        foreach($this->client->guilds->all() as $guild) {
            if($guild->available === false) {
                $unavailableGuilds++;
            }
        }
        
        if($unavailableGuilds === 0) {
            $this->client->wsmanager()->emit('ready');
        } else {
            $this->client->wsmanager()->on('guildCreate', function () {
                if($this->client->getWSstatus() === \CharlotteDunois\Yasmin\Constants::WS_STATUS_CONNECTED) {
                    return;
                }
                
                $unavailableGuilds = 0;
                foreach($this->client->guilds->all() as $guild) {
                    if($guild->available === false) {
                        $unavailableGuilds++;
                    }
                }
                
                if($unavailableGuilds === 0) {
                    $this->client->wsmanager()->emit('ready');
                }
            });
        }
    }
}
