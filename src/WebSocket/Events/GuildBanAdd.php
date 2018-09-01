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
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-ban-add
 * @internal
 */
class GuildBanAdd implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
    }
    
    function handle(\CharlotteDunois\Yasmin\WebSocket\WSConnection $ws, array $data): void {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $user = $this->client->users->patch($data);
            if($user) {
                $user = \React\Promise\resolve($user);
            } else {
                $user = $this->client->fetchUser($data['id']);
            }
        
            $user->done(function (\CharlotteDunois\Yasmin\Models\User $user) use ($guild) {
                $this->client->emit('guildBanAdd', $guild, $user);
            }, array($this->client, 'handlePromiseRejection'));
        }
    }
}
