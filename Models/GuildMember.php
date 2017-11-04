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
            $grole = $guild->roles->get($role);
            $this->roles->set($grole->id, $grole);
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
            case 'permissions':
                if($this->id === $this->guild->ownerID) {
                    return (new \CharlotteDunois\Yasmin\Models\Permissions(\CharlotteDunois\Yasmin\Models\Permissions::ALL));
                }
                
                $permissions = 0;
                foreach($this->roles->all() as $role) {
                    $permissions |= $role->permissions->bitfield;
                }
                
                return (new \CharlotteDunois\Yasmin\Models\Permissions($permissions));
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
     * Adds a role to the guild member.
     * @param \CharlotteDunois\Yasmin\Models\Role|string   $role    A role object or role ID.
     * @param string                                       $reason
     * @return \React\Promise\Promise<this>
     */
    function addRole($role, string $reason = '') {
        if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
            $role = $role->id;
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($role, $reason) {
            $this->client->apimanager()->endpoints->guild->addGuildMemberRole($this->guild->id, $this->id, $role, $reason)->then(function ($data) use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Adds roles to the guild member.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array<\CharlotteDunois\Yasmin\Models\Role>   $roles    A collection or array of role objects (or role IDs).
     * @param string                                                                                $reason
     * @return \React\Promise\Promise<this>
     */
    function addRoles($roles, string $reason = '') {
        if($roles instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
            $roles = $roles->all();
        }
        
        $roles = \array_merge($this->roles, $roles);
        return $this->edit(array('roles' => $roles), $reason);
    }
    
    /**
     * Bans the guild member.
     * @param int     $days     Number of days of messages to delete (0-7).
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     */
    function ban(int $days = 0, string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($days, $reason) {
            $this->client->apimanager()->endpoints->guild->createGuildBan($this->guild->id, $this->id, $days, $reason)->then(function ($data) use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Edits the guild member. Options are as following (only one required):
     *
     *  array(
     *      'nick' => string,
     *      'roles' => array|\CharlotteDunois\Yasmin\Utils\Collection, (of role objects or role IDs)
     *      'deaf' => bool,
     *      'mute' => bool,
     *      'channel' => \CharlotteDunois\Yasmin\Models\VoiceChannel|string (if member is connected to voice)
     *  )
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \InvalidArgumentException
     */
    function edit(array $options, string $reason = '') {
        $data = array();
        
        if(isset($options['nick'])) {
            $data['nick'] = (string) $options['nick'];
        }
        
        if(isset($options['roles'])) {
            if($options['roles'] instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                $options['roles'] = $options['roles']->all();
            }
            
            $data['roles'] = \array_unique(\array_map($options['roles'], function ($role) {
                if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
                    return $role->id;
                }
                
                return $role;
            }));
        }
        
        if(isset($options['deaf'])) {
            $data['deaf'] = (bool) $options['deaf'];
        }
        
        if(isset($options['mute'])) {
            $data['mute'] = (bool) $options['mute'];
        }
        
        if(isset($options['channel'])) {
            $data['channel_id'] = $this->guild->channels->resolve($options['channel']);
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($data, $reason) {
            $this->client->apimanager()->endpoints->guild->modifyGuildMember($this->guild->id, $this->id, $data, $reason)->then(function ($data) use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Kicks the guild member.
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     */
    function kick(string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->guild->removeGuildMember($this->guild->id, $this->id, $reason)->then(function ($data) use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Returns permissions for a member in a guild channel, taking into account roles and permission overwrites.
     * @param  \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface|string  $channel
     * @return \CharlotteDunois\Yasmin\Models\Permissions
     * @throws \InvalidArgumentException
     */
    function permissionsIn($channel) {
        $channel = $this->guild->channels->resolve($channel);
        return $channel->permissionsFor($this);
    }
    
    /**
     * Removes a role from the guild member.
     * @param \CharlotteDunois\Yasmin\Models\Role|string   $role    A role object or role ID.
     * @param string                                       $reason
     * @return \React\Promise\Promise<this>
     */
    function removeRole($role, string $reason = '') {
        if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
            $role = $role->id;
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($role, $reason) {
            $this->client->apimanager()->endpoints->guild->removeGuildMemberRole($this->guild->id, $this->id, $role, $reason)->then(function ($data) use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Removes roles from the guild member.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array<\CharlotteDunois\Yasmin\Models\Role>   $roles    A collection or array of role objects (or role IDs).
     * @param string                                                                                $reason
     * @return \React\Promise\Promise<this>
     */
    function removeRoles($roles, string $reason = '') {
        if($roles instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
            $roles = $roles->all();
        }
        
        $roles = \array_filter($this->roles, function ($role) {
            return (!\in_array($role, $roles, true));
        });
        
        return $this->edit(array('roles' => $roles), $reason);
    }
    
    /**
     * Deafen/undeafen a guild member.
     * @param bool    $deaf
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     */
    function setDeaf(bool $deaf, string $reason = '') {
        return $this->edit(array('deaf' => $deaf), $reason);
    }
    
    /**
     * Mute/unmute a guild member.
     * @param bool    $mute
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     */
    function setMute(bool $mute, string $reason = '') {
        return $this->edit(array('mute' => $mute), $reason);
    }
    
    /**
     * Set the nickname of the guild member.
     * @param string  $nickname
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     */
    function setNickname(string $nickname, string $reason = '') {
        if($this->id === $this->client->user->id) {
            return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($nickname) {
                $this->client->apimanager()->endpoints->guild->modifyCurrentNick($this->guild->id, $this->id, $nickname)->then(function ($data) use ($resolve) {
                    $resolve($this);
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }));
        }
        
        return $this->edit(array('nick' => $nickname), $reason);
    }
    
    /**
     * Sets the roles of the guild member.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array<\CharlotteDunois\Yasmin\Models\Role>   $roles    A collection or array of role objects (or role IDs).
     * @param string                                                                                $reason
     * @return \React\Promise\Promise<this>
     */
    function setRoles($roles, string $reason = '') {
        return $this->edit(array('roles' => $roles), $reason);
    }
    
    /**
     * Moves the guild member to the given voice channel, if connected to voice.
     * @param \CharlotteDunois\Yasmin\Models\VoiceChannel|string  $channel
     * @param string                                              $reason
     * @return \React\Promise\Promise<this>
     * @throws \InvalidArgumentException
     */
    function setVoiceChannel($channel) {
        return $this->edit(array('channel' => $channel));
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
