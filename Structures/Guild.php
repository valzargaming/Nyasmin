<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

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
    
    function __construct($client, $guild) {
        parent::__construct($client);
        
        $this->client->guilds->set($guild['id'], $this);
        
        $this->channels = new \CharlotteDunois\Yasmin\Structures\ChannelStorage($client);
        $this->emojis = new \CharlotteDunois\Yasmin\Structures\Collection();
        $this->members = new \CharlotteDunois\Yasmin\Structures\GuildMemberStorage($client);
        $this->presences = new \CharlotteDunois\Yasmin\Structures\PresenceStorage($client);
        $this->roles = new \CharlotteDunois\Yasmin\Structures\RoleStorage($client);
        $this->voiceStates = new \CharlotteDunois\Yasmin\Structures\Collection();
        
        $this->id = $guild['id'];
        $this->name = $guild['name'];
        $this->icon = $guild['icon'];
        $this->splash = $guild['splash'];
        $this->unavailable = (!empty($guild['unavailable']));
        $this->ownerID = $guild['owner_id'];
        $this->large = (isset($guild['large']) ? $guild['large'] : null);
        $this->memberCount = (!empty($guild['member_count']) ? $guild['member_count'] : 0);
        
        $this->defaultMessageNotifications = $guild['default_message_notifications'];
        $this->explicitContentFilter = $guild['explicit_content_filter'];
        $this->region = $guild['region'];
        $this->verificationLevel = $guild['verification_level'];
        
        $this->afkChannelID = $guild['afk_channel_id'];
        $this->afkTimeout = $guild['afk_timeout'];
        $this->features = $guild['features'];
        $this->mfaLevel = $guild['mfa_level'];
        
        $this->applicationID = $guild['application_id'];
        $this->embedEnabled = $guild['embed_enabled'];
        $this->embedChannelID = $guild['embed_channel_id'];
        $this->widgetEnabled = $guild['widget_enabled'];
        $this->widgetChannelID = $guild['widget_channel_id'];
        
        foreach($guild['emojis'] as $emoji) {
            $this->emojis->set($emoji['id'], $emoji);
        }
        
        foreach($guild['roles'] as $role) {
            $this->roles->set($role['id'], $role);
        }
        
        if(!empty($guild['channels'])) {
            foreach($guild['channels'] as $channel) {
                $this->channels->set($channel['id'], \CharlotteDunois\Yasmin\Structures\GuildChannel::factory($this->client, $this, $channel));
            }
        }
        
        if(!empty($guild['members'])) {
            foreach($guild['members'] as $member) {
                $this->members->set($member['id'], new \CharlotteDunois\Yasmin\Structures\GuildMember($this->client, $this, $member));
            }
        }
        
        if(!empty($guild['presences'])) {
            foreach($guild['presences'] as $presence) {
                $this->presences->set($presence['id'], new \CharlotteDunois\Yasmin\Structures\Presence($this->client, $presence));
            }
        }
        
        if(!empty($guild['voice_states'])) {
            foreach($guild['voice_states'] as $state) {
                $this->voiceStates->set($state['id'], new \CharlotteDunois\Yasmin\Structures\VoiceState($state));
            }
        }
        
        $this->createdTimestamp = \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->getTimestamp();
    }
}
