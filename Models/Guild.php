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
 * Represents a guild.
 */
class Guild extends ClientBase { //TODO: Implementation
    protected $channels;
    protected $emojis;
    protected $members;
    protected $presences;
    protected $roles;
    protected $voiceStates;
    
    protected $id;
    protected $available;
    
    protected $name;
    protected $icon;
    protected $splash;
    protected $ownerID;
    protected $large;
    protected $memberCount = 0;
    
    protected $defaultMessageNotifications;
    protected $explicitContentFilter;
    protected $region;
    protected $verificationLevel;
    protected $systemChannelID;
    
    protected $afkChannelID;
    protected $afkTimeout;
    protected $features;
    protected $mfaLevel;
    protected $applicationID;
    
    protected $embedEnabled;
    protected $embedChannelID;
    protected $widgetEnabled;
    protected $widgetChannelID;
    
    protected $createdTimestamp;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $guild) {
        parent::__construct($client);
        
        $this->client->guilds->set($guild['id'], $this);
        
        $this->channels = new \CharlotteDunois\Yasmin\Models\ChannelStorage($client);
        $this->emojis = new \CharlotteDunois\Yasmin\Models\Collection();
        $this->members = new \CharlotteDunois\Yasmin\Models\GuildMemberStorage($client, $this);
        $this->presences = new \CharlotteDunois\Yasmin\Models\PresenceStorage($client);
        $this->roles = new \CharlotteDunois\Yasmin\Models\RoleStorage($client, $this);
        $this->voiceStates = new \CharlotteDunois\Yasmin\Models\Collection();
        
        $this->id = $guild['id'];
        $this->available = (empty($guild['unavailable']));
        
        if($this->available) {
            $this->_patch($guild);
        }
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @access private
     */
    function _patch(array $guild) {
        $this->available = (empty($guild['unavailable']));
        
        $this->name = $guild['name'];
        $this->icon = $guild['icon'];
        $this->splash = $guild['splash'];
        $this->ownerID = $guild['owner_id'];
        $this->large =  $guild['large'] ?? $this->large;
        $this->memberCount = $guild['member_count']  ?? $this->memberCount;
        
        $this->defaultMessageNotifications = $guild['default_message_notifications'];
        $this->explicitContentFilter = $guild['explicit_content_filter'];
        $this->region = $guild['region'];
        $this->verificationLevel = $guild['verification_level'];
        $this->systemChannelID = $guild['system_channel_id'];
        
        $this->afkChannelID = $guild['afk_channel_id'];
        $this->afkTimeout = $guild['afk_timeout'];
        $this->features = $guild['features'];
        $this->mfaLevel = $guild['mfa_level'];
        $this->applicationID = $guild['application_id'];
        
        $this->embedEnabled = $guild['embed_enabled'] ?? $this->embedEnabled;
        $this->embedChannelID = $guild['embed_channel_id'] ?? $this->embedChannelID;
        $this->widgetEnabled = $guild['widget_enabled'] ?? $this->widgetEnabled;
        $this->widgetChannelID = $guild['widget_channel_id'] ?? $this->widgetChannelID;
        
        foreach($guild['roles'] as $role) {
            $this->roles->set($role['id'], (new \CharlotteDunois\Yasmin\Models\Role($this->client, $this, $role)));
        }
        
        foreach($guild['emojis'] as $emoji) {
            $this->emojis->set($emoji['id'], (new \CharlotteDunois\Yasmin\Models\Emoji($this->client, $this, $emoji)));
        }
        
        if(!empty($guild['channels'])) {
            foreach($guild['channels'] as $channel) {
                $this->channels->set($channel['id'], \CharlotteDunois\Yasmin\Models\GuildChannel::factory($this->client, $this, $channel));
            }
        }
        
        if(!empty($guild['members'])) {
            foreach($guild['members'] as $member) {
                $this->_addMember($member, true);
            }
        }
        
        if(!empty($guild['presences'])) {
            foreach($guild['presences'] as $presence) {
                $this->presences->factory($presence);
            }
        }
        
        if(!empty($guild['voice_states'])) {
            foreach($guild['voice_states'] as $state) {
                $voice = new \CharlotteDunois\Yasmin\Models\VoiceState($this->client, $this->channels->get($state['channel_id']), $state);
                $client->voiceStates->set($state['user_id'], $voice);
                $this->voiceStates->set($state['user_id'], $voice);
            }
        }
    }
    
    /**
     * @property-read string                                          $id                  The guild ID.
     * @property-read string                                          $name                The guild name.
     * @property-read int                                             $createdTimestamp    The timestmap when this guild was created.
     * @property-read string|null                                     $icon                The guild icon hash, or null.
     * @property-read string|null                                     $splash              The guild splash hash, or null.
     *
     * @property-read \DateTime                                       $createdAt           The DateTime object of createdTimestamp.
     * @property-read string|null                                     $iconURL             The guild icon URL, or null.
     * @property-read \CharlotteDunois\Yasmin\Models\GuildMember      $me                  The guild member of the client user.
     * @property-read string|null                                     $splashURL           The guild splash URL, or null.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'iconURL':
                if($this->icon) {
                    return \CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['icons'], $this->id, $this->icon);
                }
            break;
            case 'me':
                return $this->members->get($this->client->getClientUser()->id);
            break;
            case 'splashURL':
                if($this->splash) {
                    return \CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['splashes'], $this->id, $this->splash);
                }
            break;
        }
        
        return null;
    }
    
    /**
     * Fetches a specific guild member.
     * @param string  $userid  The ID of the guild member.
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Models\GuildMember>
     */
    function fetchMember(string $userid) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($userid) {
            $this->client->apimanager()->endpoints->guild->getGuildMember($this->id, $userid)->then(function ($data) use ($resolve) {
                $resolve($this->_addMember($data));
            }, $reject);
        }));
    }
    
    /**
     * Fetches all guild members.
     * @param string  $query  Limit fetch to members with similar usernames
     * @param int     $limit  Maximum number of members to request
     * @return \React\Promise\Promise<this>
     */
    function fetchMembers(string $query = '', int $limit = 0) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($query, $limit) {
            if($this->members->count() === $this->memberCount) {
                $resolve($this);
                return;
            }
            
            $listener = function ($guild) use(&$listener, $resolve, $reject) {
                if($guild->id !== $this->id) {
                    return;
                }
                
                if($this->members->count() === $this->memberCount) {
                    $this->client->removeListener('guildMembersChunk', $listener);
                    $resolve($this);
                }
            };
            
            $this->client->on('guildMembersChunk', $listener);
            
            $this->client->wsmanager()->send(array(
                'op' => \CharlotteDunois\Yasmin\Constants::OPCODES['REQUEST_GUILD_MEMBERS'],
                'd' => array(
                    'guild_id' => $this->id,
                    'query' => $query ?? '',
                    'limit' => $limit ?? 0
                )
            ));
            
            $this->client->addTimer(120, function () use ($reject) {
                if($this->members->count() < $this->memberCount) {
                    $reject(new \Exception('Members did not arrive in time'));
                }
            });
        }));
    }
    
    /**
     * @access private
     */
    function _addMember(array $member, bool $initial = false) {
        $guildmember = $this->members->factory($member);
        
        if(!$initial) {
            $this->memberCount++;
        }
        
        return $guildmember;
    }
    
    /**
     * @access private
     */
    function _removeMember(string $userid) {
        if($this->members->has($userid)) {
            $member = $this->members->get($userid);
            $this->members->delete($userid);
            
            $this->memberCount--;
            return $member;
        }
        
        return null;
    }
}
