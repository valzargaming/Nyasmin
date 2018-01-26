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
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-create
 * @internal
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
            if($guild->available === false && ($data['unavailable'] ?? false) === false) {
                $guild->_patch($data);
            }
        } else {
            $guild = $this->client->guilds->factory($data);
        }
        
        if($guild->available === false) {
            if($this->ready) {
                $this->client->emit('guildUnavailable', $guild);
            } else {
                $this->client->wsmanager()->emit('guildCreate');
            }
            
            return;
        }
        
        if(((bool) $this->client->getOption('fetchAllMembers', false)) === true && $guild->members->count() < $guild->memberCount) {
            $fetchAll = $guild->fetchMembers();
        } elseif($guild->me === null) {
            $fetchAll = $guild->fetchMember($this->client->user->id);
        } else {
            $fetchAll = \React\Promise\resolve();
        }
        
        $fetchAll->then(function () use ($guild) {
            if($this->ready) {
                $this->client->emit('guildCreate', $guild);
            } else {
                $this->client->wsmanager()->emit('guildCreate');
            }
        })->done(null, array($this->client, 'handlePromiseRejection'));
    }
}
