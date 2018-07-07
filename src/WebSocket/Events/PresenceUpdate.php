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
class PresenceUpdate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('presenceUpdate', (array) $clones));
    }
    
    function handle(array $data) {
        $user = $this->client->users->get($data['user']['id']);
        
        if(($data['status'] ?? null) === 'offline' && $user === null) {
            return;
        }
        
        if($user === null) {
            $user = $this->client->fetchUser($data['user']['id']);
        } else {
            if(\count($data['user']) > 1 && $user->_shouldUpdate($data['user'])) {
                $oldUser = null;
                if($this->clones) {
                    $oldUser = clone $user;
                }
                
                $user->_patch($data['user']);
                
                $this->client->emit('userUpdate', $user, $oldUser);
                return;
            }
            
            $user = \React\Promise\resolve($user);
        }
        
        $user->done(function ($user) use ($data) {
            $guild = $this->client->guilds->get($data['guild_id']);
            if($guild) {
                $presence = $guild->presences->get($user->id);
                $oldPresence = null;
                
                if($presence) {
                    if($data['status'] === 'offline' && $presence->status === 'offline') {
                        return;
                    }
                    
                    if($this->clones) {
                        $oldPresence = clone $presence;
                    }
                    
                    $presence->_patch($data);
                } else {
                    $presence = $guild->presences->factory($data);
                }
                
                $this->client->emit('presenceUpdate', $presence, $oldPresence);
            }
        }, array($this->client, 'handlePromiseRejection'));
    }
}
