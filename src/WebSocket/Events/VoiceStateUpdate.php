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
 * @see https://discordapp.com/developers/docs/topics/gateway#voice-state-update
 * @internal
 */
class VoiceStateUpdate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('voiceStateUpdate', (array) $clones));
    }
    
    function handle(array $data): void {
        $user = $this->client->users->get($data['user_id']);
        if($user) {
            if(empty($data['channel_id'])) {
                if(empty($data['guild_id'])) {
                    return;
                }
                
                $guild = $this->client->guilds->get($data['guild_id']);
                if($guild) {
                    if($guild->members->has($data['user_id'])) {
                        $member = \React\Promise\resolve($guild->members->get($data['user_id']));
                    } elseif(!empty($data['member'])) {
                        $member = $data['member'];
                        $member['user'] = $user->id;
                        $member = \React\Promise\resolve($guild->_addMember($member, true));
                    } else {
                        $member = $guild->fetchMember($user->id);
                    }
                    
                    $member->done(function (\CharlotteDunois\Yasmin\Models\GuildMember $member) use ($data) {
                        $oldMember = null;
                        if($this->clones) {
                            $oldMember = clone $member;
                        }
                        
                        if($member->voiceChannel) {
                            $member->voiceChannel->members->delete($member->id);
                        }
                        
                        $member->_setVoiceState($data);
                        $this->client->emit('voiceStateUpdate', $member, $oldMember);
                    }, function () use ($guild, $user) {
                        foreach($guild->channels as $channel) {
                            if($channel->type instanceof \CharlotteDunois\Yasmin\Models\VoiceChannel) {
                                $channel->members->delete($user->id);
                            }
                        }
                    });
                }
            } else {
                $channel = $this->client->channels->get($data['channel_id']);
                if($channel) {
                    if($channel->guild === null) {
                        return;
                    }
                    
                    $channel->guild->fetchMember($user->id)->done(function (\CharlotteDunois\Yasmin\Models\GuildMember $member) use ($data, $channel) {
                        $oldMember = null;
                        if($this->clones) {
                            $oldMember = clone $member;
                        }
                        
                        $member->_setVoiceState($data);
                        $channel->members->delete($member->id);
                        $channel->members->set($member->id, $member);
                        
                        $this->client->emit('voiceStateUpdate', $member, $oldMember);
                    }, function () use ($channel, $user) {
                        $channel->members->delete($user->id);
                    });
                }
            }
        }
    }
}
