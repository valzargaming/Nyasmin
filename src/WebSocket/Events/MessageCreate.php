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
 * @see https://discordapp.com/developers/docs/topics/gateway#message-create
 * @internal
 */
class MessageCreate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->_createMessage($data);
            
            if($message->mentions->users->count() > 0 && $message->mentions->users->count() > $message->mentions->members->count()) {
                $promise = array();
                
                foreach($message->mentions->users as $user) {
                    $promise[] = $message->guild->fetchMember($user->id)->then(function ($member) use ($message) {
                        $message->mentions->members->set($member->id, $member);
                    }, function ($error) {
                        $this->client->emit('error', $error);
                    });
                }
                
                $prm = \React\Promise\all($promise);
            } else {
                $prm = \React\Promise\resolve();
            }
            
            $prm->then(function () use ($message) {
                if($message->guild && !$message->member && !$message->author->webhook) {
                    return $message->guild->fetchMember($message->author->id)->then(function () use ($message) {
                        $this->client->emit('message', $message);
                    }, function () use ($message) {
                        $this->client->emit('message', $message);
                    });
                } else {
                    $this->client->emit('message', $message);
                }
            })->done(null, array($this->client, 'handlePromiseRejection'));
        }
    }
}
