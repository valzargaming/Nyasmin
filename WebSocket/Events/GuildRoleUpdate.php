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
 * @link https://discordapp.com/developers/docs/topics/gateway#guild-role-update
 * @access private
 */
class GuildRoleCreate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $role = $guild->roles->get($data['role']['id']);
            if($role) {
                $oldRole = clone $role;
                
                $role->_patch($data['role']);
                $this->client->emit('roleCreate', $role, $oldRole);
            }
        }
    }
}
