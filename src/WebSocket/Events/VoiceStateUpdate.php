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
 * @see https://discordapp.com/developers/docs/topics/gateway#voice-state-update
 * @internal
 */
class VoiceStateUpdate {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('voiceStateUpdate', (array) $clones));
    }
    
    function handle(array $data) {
        $user = $this->client->users->get($data['user_id']);
        if($user) {
            if(empty($data['channel_id'])) {
                if(empty($data['guild_id'])) {
                    return;
                }
                
                $guild = $this->client->guilds->get($data['guild_id']);
                $guild->fetchMember($user->id)->then(function ($member) use ($data, $guild, $user) {
                    $oldMember = null;
                    if($this->clones) {
                        $oldMember = clone $member;
                    }
                    
                    if($member->voiceChannel) {
                        $member->voiceChannel->members->delete($user->id);
                    }
                    
                    $member->_setVoiceState($data);
                    $this->client->emit('voiceStateUpdate', $member, $oldMember);
                }, function () use ($guild, $user) {
                    foreach($guild->channels as $channel) {
                        if($channel->type instanceof \CharlotteDunois\Yasmin\Models\VoiceChannel) {
                            $channel->members->delete($user->id);
                        }
                    }
                })->done(null, array($this->client, 'handlePromiseRejection'));
            } else {
                $channel = $this->client->channels->get($data['channel_id']);
                if($channel) {
                    if($channel->guild === null) {
                        return;
                    }
                    
                    $channel->guild->fetchMember($user->id)->then(function ($member) use ($data, $channel, $user) {
                        $oldMember = null;
                        if($this->clones) {
                            $oldMember = clone $member;
                        }
                        
                        $member->_setVoiceState($data);
                        $channel->members->delete($user->id);
                        $channel->members->set($user->id, $member);
                        
                        $this->client->emit('voiceStateUpdate', $member, $oldMember);
                    }, function () use ($channel, $user) {
                        $channel->members->delete($user->id);
                    })->done(null, array($this->client, 'handlePromiseRejection'));
                }
            }
        }
    }
}
