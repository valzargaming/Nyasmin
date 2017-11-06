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
    protected $ready = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $wsmanager->once('ready', function () {
            $this->ready = true;
        });
    }
    
    function handle($data) {
        $this->client->emit('debug', 'Connected to Gateway version '.$data['v']);
        
        $this->client->wsmanager()->setSessionID($data['session_id']);
        
        if($this->ready) {
            $this->client->user->_patch($data['user']);
            $this->client->wsmanager()->emit('ready');
            return;
        }
        
        $this->client->setClientUser($data['user']);
        
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
