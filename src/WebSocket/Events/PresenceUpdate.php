<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @see https://discordapp.com/developers/docs/topics/gateway#presence-update
 * @internal
 */
class PresenceUpdate {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('presenceUpdate', (array) $clones));
    }
    
    function handle($data) {
        try {
            $user = $this->client->users->resolve($data['user']['id']);
            
            if(\count($data['user']) > 1 && $user->_shouldUpdate($data['user'])) {
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
        } catch(\Throwable | \Exception | \Error $e) {
            /* Continue regardless of error */
        }
    }
}
