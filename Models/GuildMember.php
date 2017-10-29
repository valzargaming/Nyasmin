<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

class GuildMember extends ClientBase { //TODO: Implementation
    protected $guild;
    
    protected $id;
    protected $user;
    protected $nickname;
    protected $deaf;
    protected $mute;
    
    protected $joinedTimestamp;
    protected $roles;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $member) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->id = $member['user']['id'];
        $this->user = $this->client->users->patch($member['user']);
        $this->nickname = $member['nick'] ?? null;
        $this->deaf = $member['deaf'];
        $this->mute = $member['mute'];
        
        $this->joinedTimestamp = (new \DateTime((!empty($member['joined_at']) ? $member['joined_at'] : 'now')))->getTimestamp();
        
        $this->roles = new \CharlotteDunois\Yasmin\Utils\Collection();
        $this->roles->set($this->guild->id, $this->guild->roles->get($this->guild->id));
        
        foreach($member['roles'] as $role) {
            $role = $guild->roles->get($role);
            $this->roles->set($role->id, $role);
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
                return ($this->nickname ?? $this->user->username);
            break;
            case 'highestRole':
            break;
            case 'hoistRole':
            break;
            case 'joinedAt':
                return (new \DateTime($this->joinedTimestamp));
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
                $vc = $this->__get('voiceChannel');
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
    
    /**
     * @internal
     */
    function _patch(array $data) {
        if(!isset($data['nick']) && $this->nickname) {
            $this->nickname = null;
        } elseif($data['nick'] !== $this->nickname) {
            $this->nickname = $data['nick'];
        }
        
        foreach($this->roles->all() as $id => $role) {
            if(!\in_array($id, $data['roles'])) {
                $this->roles->delete($id);
            }
        }
        
        foreach($data['roles'] as $role) {
            if(!$this->roles->has($role)) {
                $this->roles->set($role, $this->guild->roles->get($role));
            }
        }
    }
}
