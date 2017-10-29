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
        
        $clones = (array) $this->client->getOption('disableClones', array());
        $this->clones = !\in_array('voiceStateUpdate', $clones);
    }
    
    function handle(array $data) {
        $user = $this->client->users->get($data['user_id']);
        if($user) {
            $channel = $this->client->channels->get($data['channel_id']);
            if($channel) {
                $oldVoice = null;
                if($channel->guild) {
                    $voice = $channel->guild->voiceStates->get($user->id);
                } else {
                    $voice = $this->client->voiceStates->get($user->id);
                }
                
                if($voice) {
                    if($this->clones) {
                        $oldVoice = clone $voice;
                    }
                    
                    $voice->_patch($data);
                    $voice->_updateChannel($channel);
                } else {
                    $voice = new \CharlotteDunois\Yasmin\Models\VoiceState($this->client, $channel, $data);
                    
                    if($channel->guild) {
                        $channel->guild->voiceStates->set($user->id, $voice);
                    } else {
                        $this->client->voiceStates->set($user->id, $voice);
                    }
                }
                
                $this->client->emit('voiceStateUpdate', $voice, $oldVoice);
            }
        }
    }
}
