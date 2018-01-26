<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Handlers;

/**
 * WS Event handler
 * @internal
 */
class Dispatch {
    private $wsevents = array();
    protected $wshandler;
    
    function __construct(\CharlotteDunois\Yasmin\WebSocket\WSHandler $wshandler) {
        $this->wshandler = $wshandler;
        
        $allEvents = array(
            'RESUMED' => '\CharlotteDunois\Yasmin\WebSocket\Events\Resumed',
            'READY' => '\CharlotteDunois\Yasmin\WebSocket\Events\Ready',
            'CHANNEL_CREATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelCreate',
            'CHANNEL_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelUpdate',
            'CHANNEL_DELETE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelDelete',
            'CHANNEL_PINS_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\ChannelPinsUpdate',
            'GUILD_CREATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildCreate',
            'GUILD_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildUpdate',
            'GUILD_DELETE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildDelete',
            'GUILD_BAN_ADD' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildBanAdd',
            'GUILD_BAN_REMOVE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildBanRemove',
            'GUILD_EMOJIS_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildEmojisUpdate',
            'GUILD_INTEGRATIONS_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildIntegrationsUpdate',
            'GUILD_MEMBER_ADD' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildMemberAdd',
            'GUILD_MEMBER_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildMemberUpdate',
            'GUILD_MEMBER_REMOVE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildMemberRemove',
            'GUILD_MEMBERS_CHUNK' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildMembersChunk',
            'GUILD_ROLE_CREATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildRoleCreate',
            'GUILD_ROLE_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildRoleUpdate',
            'GUILD_ROLE_DELETE' => '\CharlotteDunois\Yasmin\WebSocket\Events\GuildRoleDelete',
            'MESSAGE_CREATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageCreate',
            'MESSAGE_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageUpdate',
            'MESSAGE_DELETE' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageDelete',
            'MESSAGE_DELETE_BULK' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageDeleteBulk',
            'MESSAGE_REACTION_ADD' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageReactionAdd',
            'MESSAGE_REACTION_REMOVE' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageReactionRemove',
            'MESSAGE_REACTION_REMOVE_ALL' => '\CharlotteDunois\Yasmin\WebSocket\Events\MessageReactionRemoveAll',
            'PRESENCE_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\PresenceUpdate',
            'TYPING_START' => '\CharlotteDunois\Yasmin\WebSocket\Events\TypingStart',
            'USER_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\UserUpdate',
            'VOICE_STATE_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\VoiceStateUpdate',
            'VOICE_SERVER_UPDATE' => '\CharlotteDunois\Yasmin\WebSocket\Events\VoiceServerUpdate'
        );
        
        $events = \array_diff_key($allEvents, \array_flip((array) $this->wshandler->client->getOption('ws.disabledEvents', array())));
        foreach($events as $name => $class) {
            $this->register($name, $class);
        }
    }
    
    function getEvent(string $name) {
        if(isset($this->wsevents[$name])) {
            return $this->wsevents[$name];
        }
        
        throw new \Exception('Unable to find WS event');
    }
    
    function handle(array $packet) {
        if(isset($this->wsevents[$packet['t']])) {
            try {
                $this->wshandler->wsmanager->emit('debug', 'Handling WS event '.$packet['t']);
                $this->wsevents[$packet['t']]->handle($packet['d']);
            } catch(\Throwable | \Exception | \Error $e) {
                $this->wshandler->client->emit('error', $e);
            }
        } else {
            $this->wshandler->wsmanager->emit('debug', 'Received WS event '.$packet['t']);
        }
    }
    
    private function register($name, $class) {
        $this->wsevents[$name] = new $class($this->wshandler->client, $this->wshandler->wsmanager);
    }
}
