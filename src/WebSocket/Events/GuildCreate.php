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
class GuildCreate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $ready = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $this->client->once('ready', function () {
            $this->ready = true;
        });
    }
    
    function handle(\CharlotteDunois\Yasmin\WebSocket\WSConnection $ws, array $data): void {
        $guild = $this->client->guilds->get($data['id']);
        if($guild) {
            if(empty($data['unavailable'])) {
                $guild->_patch($data);
            }
            
            if($this->ready) {
                $this->client->emit('guildUnavailable', $guild);
            } else {
                $this->client->wsmanager()->emit('guildCreate');
            }
        } else {
            $guild = $this->client->guilds->factory($data, $ws->shardID);
            
            if(((bool) $this->client->getOption('fetchAllMembers', false)) && $guild->members->count() < $guild->memberCount) {
                $fetchAll = $guild->fetchMembers();
            } elseif($guild->me === null) {
                $fetchAll = $guild->fetchMember($this->client->user->id);
            } else {
                $fetchAll = \React\Promise\resolve();
            }
            
            $fetchAll->done(function () use ($guild) {
                if($this->ready) {
                    $this->client->emit('guildCreate', $guild);
                } else {
                    $this->client->wsmanager()->emit('guildCreate');
                }
            }, array($this->client, 'handlePromiseRejection'));
        }
    }
}
