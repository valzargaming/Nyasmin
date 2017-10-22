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
 * @link https://discordapp.com/developers/docs/topics/gateway#presence-update
 * @access private
 */
class PresenceUpdate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle($data) {
        try {
            $user = $this->client->users->resolve($data['user']['id']);
            
            $presence = $user->presence;
            if($presence) {
                $presence->_patch($data);
            } else {
                $guild = $this->client->guilds->resolve($data['guild_id']);
                $presence = $guild->presences->factory($data);
            }
            
            $this->client->emit('presenceUpdate', $presence);
        } catch(\Exception $e) {
            /* Continue regardless of error */
        }
    }
}
