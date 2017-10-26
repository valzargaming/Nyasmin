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
 * @link https://discordapp.com/developers/docs/topics/gateway#guild-role-update
 * @access private
 */
class GuildRoleUpdate {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $clones = (array) $this->client->getOption('disableClones', array());
        $this->clones = !\in_array('roleUpdate', $clones);
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $role = $guild->roles->get($data['role']['id']);
            if($role) {
                $oldRole = null;
                if($this->clones) {
                    $oldRole = clone $role;
                }
                
                $role->_patch($data['role']);
                $this->client->emit('roleUpdate', $role, $oldRole);
            }
        }
    }
}
