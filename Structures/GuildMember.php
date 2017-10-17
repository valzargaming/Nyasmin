<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class GuildMember extends Structure { //TODO: Implementation
    protected $guild;
    
    protected $id;
    protected $user;
    protected $nickname;
    
    protected $joinedAt;
    protected $joinedTimestamp;
    
    protected $deaf;
    protected $mute;
    protected $roles;
    
    function __construct($client, $guild, $member) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->roles = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->id = $member['user']['id'];
        $this->user = $this->client->users->patch($member['user']);
        $this->nickname = $member['nickname'] ?? null;
        
        $this->joinedAt = new \DateTime($member['joined_at']);
        $this->joinedTimestamp = $this->joinedAt->format('U');
        
        $this->deaf = $member['deaf'];
        $this->mute = $member['mute'];
        
        foreach($this->roles as $role) {
            $this->roles->set($role['id'], $guild->roles->get($role['id']));
        }
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'bannable':
            break;
            case 'colorRole':
            break;
            case 'displayColor':
            break;
            case 'displayHexColor':
            break;
            case 'displayName':
                return $this->nickname ?? $this->user->username;
            break;
            case 'highestRole':
            break;
            case 'hoistRole':
            break;
            case 'kickable':
            break;
            case 'presence':
            break;
            case 'speaking':
            break;
            case 'voiceChannel':
                $vc = $this->guild->channels->first(function ($channel) {
                    return ($channel->type === 'voice' && $channel->members->has($this->id));
                });
                
                if($vc) {
                    return $vc;
                }
            break;
            case 'voiceChannelID':
                $vc = $this->guild->channels->first(function ($channel) {
                    return ($channel->type === 'voice' && $channel->members->has($this->id));
                });
                
                if($vc) {
                    return $vc->id;
                }
            break;
        }
        
        return null;
    }
    
    function __toString() {
        return '<@'.(!empty($this->nickname) ? '!' : '').$this->id.'>';
    }
}
