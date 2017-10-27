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
            if($guild->available === false && $data['unavailable'] === false) {
                $guild->_patch($data);
            }
        } else {
            $guild = new \CharlotteDunois\Yasmin\Models\Guild($this->client, $data);
            $this->client->guilds->set($guild->id, $guild);
        }
        
        if(((bool) $this->client->getOption('fetchAllMembers', false)) === true && $guild->members->count() < $guild->memberCount) {
            $fetchAll = $guild->fetchMembers();
        } else {
            $fetchAll = \React\Promise\resolve();
        }
        
        $fetchAll->then(function () use ($guild) {
            if($this->ready) {
                $this->client->emit('guildCreate', $guild);
            } else {
                $this->client->wsmanager()->emit('guildCreate');
            }
        });
    }
}
