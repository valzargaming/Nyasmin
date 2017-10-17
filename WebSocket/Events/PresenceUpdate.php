<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

class PresenceUpdate {
    protected $client;
    
    function __construct($client) {
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
                $guild->presences->factory($data);
            }
        } catch(\Exception $e) {
            /* Continue regardless of error */
        }
    }
}
