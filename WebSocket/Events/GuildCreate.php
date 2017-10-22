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
 * @link https://discordapp.com/developers/docs/topics/gateway#guild-create
 * @access private
 */
class GuildCreate {
    protected $client;
    protected $ready = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $this->client->once('ready', function () {
            $this->ready = true;
        });
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['id']);
        if($guild) {
            $guild->_patch(array('unavailable' => false));
        } else {
            $guild = new \CharlotteDunois\Yasmin\Structures\Guild($this->client, $data);
            $this->client->guilds->set($guild->id, $guild);
            
            if($this->ready) {
                $this->client->emit('guildCreate', $guild);
            } else {
                $this->client->wsmanager()->emit('guildCreate');
            }
        }
    }
}
