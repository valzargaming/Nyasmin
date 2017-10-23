<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Represents a guild.
 */
class Guild extends Structure { //TODO: Implementation
    protected $channels;
    protected $emojis;
    protected $members;
    protected $presences;
    protected $roles;
    protected $voiceStates;
    
    protected $id;
    protected $name;
    protected $icon;
    protected $splash;
    protected $unavailable;
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
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $guild) {
        parent::__construct($client);
        
        $this->client->guilds->set($guild['id'], $this);
        
        $this->channels = new \CharlotteDunois\Yasmin\Structures\ChannelStorage($client);
        $this->emojis = new \CharlotteDunois\Yasmin\Structures\Collection();
        $this->members = new \CharlotteDunois\Yasmin\Structures\GuildMemberStorage($client, $this);
        $this->presences = new \CharlotteDunois\Yasmin\Structures\PresenceStorage($client);
        $this->roles = new \CharlotteDunois\Yasmin\Structures\RoleStorage($client);
        $this->voiceStates = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->id = $guild['id'];
        $this->name = $guild['name'];
        $this->icon = $guild['icon'];
        $this->splash = $guild['splash'];
        $this->unavailable = (!empty($guild['unavailable']));
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
            $this->roles->set($role['id'], (new \CharlotteDunois\Yasmin\Structures\Role($this->client, $this, $role)));
        }
        
        foreach($guild['emojis'] as $emoji) {
            $this->emojis->set($emoji['id'], (new \CharlotteDunois\Yasmin\Structures\Emoji($this->client, $this, $emoji)));
        }
        
        if(!empty($guild['channels'])) {
            foreach($guild['channels'] as $channel) {
                $this->channels->set($channel['id'], \CharlotteDunois\Yasmin\Structures\GuildChannel::factory($this->client, $this, $channel));
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
                $voice = new \CharlotteDunois\Yasmin\Structures\VoiceState($this->client, $this->channels->get($state['channel_id']), $state);
                $client->voiceStates->set($state['user_id'], $voice);
                $this->voiceStates->set($state['user_id'], $voice);
            }
        }
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'me':
                return $this->members->get($this->client->getClientUser()->id);
            break;
        }
        
        return null;
    }
    
    function fetchMember(string $userid) {
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($userid) {
            $this->client->apimanager()->endpoints->guild->getGuildMember($this->id, $userid)->then(function ($data) use ($resolve) {
                return $this->_addMember($data);
            }, $reject);
        });
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
