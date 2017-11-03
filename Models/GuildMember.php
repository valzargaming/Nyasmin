<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a guild member.
 * @todo Implementation
 */
class GuildMember extends ClientBase {
    protected $guild;
    
    protected $id;
    protected $user;
    protected $nickname;
    protected $deaf;
    protected $mute;
    protected $speaking = false;
    
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
    
    /**
     *
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'bannable':
                if($this->id === $this->guild->ownerID || $this->id === $this->client->user->id) {
                    return false;
                }
                
                $member = $this->guild->me;
                if($member->permissions->has(\CharlotteDunois\Yasmin\Constants::PERMISSIONS['BAN_MEMBERS']) === false) {
                    return false;
                }
                
                return ($member->highestRole->comparePositionTo($this->__get('highestRole')) > 0);
            break;
            case 'colorRole':
                $roles = $this->roles->filter(function ($role) {
                    return $role->color;
                });
                
                if($roles->count() === 0) {
                    return null;
                }
                
                return $roles->reduce(function ($prev, $role) {
                    if($prev === null) {
                        return $role;
                    }
                    
                    return ($role->comparePositionTo($prev) > 0 ? $role : $prev);
                });
            break;
            case 'displayColor':
                $colorRole = $this->__get('colorRole');
                if($colorRole !== null && $colorRole->color > 0) {
                    return $colorRole->color;
                }
                
                return null;
            break;
            case 'displayHexColor':
                $colorRole = $this->__get('colorRole');
                if($colorRole !== null && $colorRole->color > 0) {
                    return $colorRole->hexColor;
                }
                
                return null;
            break;
            case 'displayName':
                return ($this->nickname ?? $this->user->username);
            break;
            case 'highestRole':
                return $this->roles->reduce(function ($prev, $role) {
                    if($prev === null) {
                        return $role;
                    }
                    
                    return ($role->comparePositionTo($prev) > 0 ? $role : $prev);
                });
            break;
            case 'hoistRole':
                $roles = $this->roles->filter(function ($role) {
                    return $role->hoist;
                });
                
                if($roles->count() === 0) {
                    return null;
                }
                
                return $roles->reduce(function ($prev, $role) {
                    if($prev === null) {
                        return $role;
                    }
                    
                    return ($role->comparePositionTo($prev) > 0 ? $role : $prev);
                });
            break;
            case 'joinedAt':
                return (new \DateTime($this->joinedTimestamp));
            break;
            case 'kickable':
                if($this->id === $this->guild->ownerID || $this->id === $this->client->user->id) {
                    return false;
                }
                
                $member = $this->guild->me;
                if($member->permissions->has(\CharlotteDunois\Yasmin\Constants::PERMISSIONS['KICK_MEMBERS']) === false) {
                    return false;
                }
                
                return ($member->highestRole->comparePositionTo($this->__get('highestRole')) > 0);
            break;
            case 'presence':
                return $this->guild->presences->get($this->id);
            break;
            case 'voiceChannel':
                $vc = $this->guild->channels->first(function ($channel) {
                    return ($channel->type === 'voice' && $channel->members->has($this->id));
                });
                
                if($vc) {
                    return $vc;
                }
                
                return null;
            break;
            case 'voiceChannelID':
                $vc = $this->__get('voiceChannel');
                if($vc) {
                    return $vc->id;
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<@'.(!empty($this->nickname) ? '!' : '').$this->id.'>';
    }
    
    /**
     * @internal
     */
    function _setSpeaking(bool $speaking) {
        $this->speaking = $speaking;
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
