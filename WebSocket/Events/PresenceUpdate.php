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
 * @see https://discordapp.com/developers/docs/topics/gateway#presence-update
 * @access private
 */
class PresenceUpdate {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $clones = (array) $this->client->getOption('disableClones', array());
        $this->clones = !\in_array('presenceUpdate', $clones);
    }
    
    function handle($data) {
        try {
            $user = $this->client->users->resolve($data['user']['id']);
            
            if($user->_shouldUpdate($data['user'])) {
                $oldUser = null;
                if($this->clones) {
                    $oldUser = clone $user;
                }
                
                $user->_patch($data['user']);
                
                if($user != $oldUser) {
                    $this->client->emit('userUpdate', $user, $oldUser);
                }
            }
            
            $guild = $this->client->guilds->get($data['guild_id']);
            if($guild) {
                $presence = $guild->presences->get($user->id);
                $oldPresence = null;
                
                if($presence) {
                    if($this->clones) {
                        $oldPresence = clone $presence;
                    }
                    
                    $presence->_patch($data, true);
                } else {
                    $presence = $guild->presences->factory($data);
                }
                
                $this->client->emit('presenceUpdate', $presence, $oldPresence);
            }
        } catch(\Exception $e) {
            /* Continue regardless of error */
        }
    }
}
