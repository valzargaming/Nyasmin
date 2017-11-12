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
 * @see https://discordapp.com/developers/docs/topics/gateway#typing-start
 * @internal
 */
class TypingStart {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $user = $this->client->users->get($data['user_id']);
            if(!$user) {
                $user = $this->client->fetchUser($data['user_id']);
            } else {
                $user = \React\Promise\resolve($user);
            }
            
            $user->then(function ($user) use ($channel, $data) {
                $channel->_updateTyping($user, $data['timestamp']);
                $this->client->emit('typingStart', $channel, $user);
            });
        }
    }
}
