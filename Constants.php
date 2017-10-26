<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin;

/**
 * Holds all constants.
 */
class Constants {
    /**
     * The version of Yasmin.
     */
    const VERSION = '0.0.1';
    
    /**
     * WS OP codes.
     * @access private
     */
    const OPCODES = array(
        'DISPATCH' => 0,
        'HEARTBEAT' => 1,
        'IDENTIFY' => 2,
        'STATUS_UPDATE' => 3,
        'VOICE_STATE_UPDATE' => 4,
        'VOICE_SERVER_PING' => 5,
        'RESUME' => 6,
        'RECONNECT' => 7,
        'REQUEST_GUILD_MEMBERS' => 8,
        'INVALIDATE_SESSION' => 9,
        'HELLO' => 10,
        'HEARTBEAT_ACK' => 11,
        
        0 => 'DISPATCH',
        1 => 'HEARTBEAT',
        2 => 'IDENTIFY',
        3 => 'STATUS_UPDATE',
        4 => 'VOICE_STATE_UPDATE',
        5 => 'VOICE_SERVER_PING',
        6 => 'RESUME',
        7 => 'RECONNECT',
        8 => 'REQUEST_GUILD_MEMBERS',
        9 => 'INVALIDATE_SESSION',
        10 => 'HELLO',
        11 => 'HEARTBEAT_ACK'
    );
    
    /**
     * CDN constants.
     * @access private
     */
    const CDN = array(
        'url' => 'https://cdn.discordapp.com/',
        'emojis' => 'emojis/%s.png',
        'icons' => 'icons/%s/%s.png',
        'splashs' => 'splashes/%s/%s.png',
        'defaultavatars' => 'embed/avatars/%s.png',
        'avatars' => 'avatars/%s/%s.%s',
        'appicons' => 'app-icons/%s/%s.png'
    );
    
    /**
     * HTTP constants.
     * @access private
     */
    const HTTP = array(
        'url' => 'https://discordapp.com/api/',
        'version' => 7,
        'invite' => 'https://discord.gg'
    );
    
    /**
     * WS constants.
     * @access private
     */
    const WS = array(
        'baseurl' => 'wss://gateway.discord.gg/',
        'encoding' => 'json',
        'version' => 6
    );
    
    /**
     * WS connection status: Disconnected.
     */
    const WS_STATUS_DISCONNECTED = 0;
    
    /**
     * WS connection status: Connecting.
     */
    const WS_STATUS_CONNECTING = 1;
    
    /**
     * WS connection status: Reconnecting.
     */
    const WS_STATUS_RECONNECTING = 2;
    
    /**
     * WS connection status: Connected (not ready yet - nearly).
     */
    const WS_STATUS_NEARLY = 3;
    
    /**
     * WS connection status: Connected (ready).
     */
    const WS_STATUS_CONNECTED = 4;
    
    /**
     * WS connection status: Idling (disconnected and no reconnect planned).
     */
    const WS_STATUS_IDLE = 5;
    
    /**
     * Channel Types.
     * @access private
     */
    const CHANNEL_TYPE = array(
        0 => 'text',
        1 => 'dm',
        2 => 'voice',
        3 => 'group',
        4 => 'category',
    );
    
    /**
     * Game Types.
     * @access private
     */
    const GAME_TYPES = array(
        0 => 'Playing',
        1 => 'Streaming'
    );
    
    /**
     * Endpoints Channels.
     * @access private
     */
    const ENDPOINTS_CHANNELS = array(
        'get' => 'channels/%s',
        'modify' => 'channels/%s',
        'delete' => 'channels/%s',
        'messages' => array(
            'list' => 'channels/%s/messages',
            'get' => 'channels/%s/messages/%s',
            'create' => 'channels/%s/messages',
            'reactions' => array(
                'create' => 'channels/%s/messages/%s/reactions/%s/@me',
                'delete' => 'channels/%s/messages/%s/reactions/%s/@me',
                'deleteUser' => 'channels/%s/messages/%s/reactions/%s/%s',
                'get' => 'channels/%s/messages/%s/reactions/%s',
                'deleteAll' => 'channels/%s/messages/%s/reactions',
            ),
            'edit' => 'channels/%s/messages/%s',
            'delete' => 'channels/%s/messages/%s',
            'bulkDelete' => 'channels/%s/messages/bulk-delete'
        ),
        'permissions' => array(
            'edit' => 'channels/%s/permissions/%s',
            'delete' => 'channels/%s/permissions/%s'
        ),
        'invites' => array(
            'list' => 'channels/%s/invites',
            'create' => 'channels/%s/invites'
        ),
        'typing' => 'channels/%s/typing',
        'pins' => array(
            'list' => 'channels/%s/pins',
            'add' => 'channels/%s/pins/%s',
            'delete' => 'channels/%s/pins/%s'
        ),
        'groupDM' => array(
            'add' => 'channels/%s/recipients/%s',
            'remove' => 'channels/%s/recipients/%s'
        )
    );
    
    /**
     * Endpoints Emojis.
     * @access private
     */
    const ENDPOINTS_EMOJIS = array(
        'list' => 'guilds/%s/emojis',
        'get' => 'guilds/%s/emojis/%s',
        'create' => 'guilds/%s/emojis',
        'modify' => 'guilds/%s/emojis/%s',
        'delete' => 'guilds/%s/emojis/%s'
    );
    
    /**
     * Endpoints Guilds.
     * @access private
     */
    const ENDPOINTS_GUILDS = array(
        'get' => 'guilds/%s',
        'modify' => 'guilds/%s',
        'delete' => 'guilds/%s',
        'channels' => array(
            'list' => 'guilds/%s/channels',
            'create' => 'guilds/%s/channels',
            'modifyPositions' => 'guilds/%s/channels'
        ),
        'members' => array(
            'get' => 'guilds/%s/members/%s',
            'list' => 'guilds/%s/members',
            'add' => 'guilds/%s/members/%s',
            'modify' => 'guilds/%s/members/%s',
            'modifyCurrentNick' => 'guilds/%s/members/@me/nick',
            'addRole' => 'guilds/%s/members/%s/roles/%s',
            'removeRole' => 'guilds/%s/members/%s/roles/%s',
            'remove' => 'guilds/%s/members/%s'
        ),
        'bans' => array(
            'list' => 'guilds/%s/bans',
            'create' => 'guilds/%s/bans/%s',
            'remove' => 'guilds/%s/bans/%s'
        ),
        'roles' => array(
            'list' => 'guilds/%s/roles',
            'create' => 'guilds/%s/roles',
            'modifyPositions' => 'guilds/%s/roles',
            'modify' => 'guilds/%s/roles/%s',
            'delete' => 'guilds/%s/roles/%s'
        ),
        'prune' => array(
            'count' => 'guilds/%s/prune',
            'begin' => 'guilds/%s/prune'
        ),
        'voice' => array(
            'regions' => 'guilds/%s/regions'
        ),
        'invites' => array(
            'list' => 'guilds/%s/invites'
        ),
        'integrations' => array(
            'list' => 'guilds/%s/integrations',
            'create' => 'guilds/%s/integrations',
            'modify' => 'guilds/%s/integrations/%s',
            'delete' => 'guilds/%s/integrations/%s',
            'sync' => 'guilds/%s/integrations/%s'
        ),
        'embed' => array(
            'get' => 'guilds/%s/embed',
            'modify' => 'guilds/%s/embed'
        ),
        'audit-logs' => 'guilds/%s/audit-logs'
    );
    
    /**
     * Endpoints Invites.
     * @access private
     */
    const ENDPOINTS_INVITES = array(
        'get' => 'invites/%s',
        'delete' => 'invites/%s',
        'accept' => 'invites/%s'
    );
    
    /**
     * Endpoints Users.
     * @access private
     */
    const ENDPOINTS_USERS = array(
        'get' => 'users/%s',
        'current' => array(
            'get' => 'users/@me',
            'modify' => 'users/@me',
            'guilds' => 'users/@me/guilds',
            'leaveGuild' => 'users/@me/guilds/%s',
            'dms' => 'users/@me/channels',
            'createDM' => 'users/@me/channels',
            'createGroupDM' => 'users/@me/channels',
            'connections' => 'users/@me/connections'
        )
    );
    
    /**
     * Endpoints Voice.
     * @access private
     */
    const ENDPOINTS_VOICE = array(
        'regions' => 'voice/regions'
    );
    
    /**
     * Endpoints Webhooks.
     * @access private
     */
    const ENDPOINTS_WEBHOOKS = array(
        'create' => 'channels/%s/webhooks',
        'channels' => 'channels/%s/webhooks',
        'guilds' => 'guilds/%s/webhooks',
        'get' => 'webhooks/%s',
        'getToken' => 'webhooks/%s/%s',
        'modify' => 'webhooks/%s',
        'modifyToken' => 'webhooks/%s/%s',
        'delete' => 'webhooks/%s',
        'deleteToken' => 'webhooks/%s/%s',
        'execute' => 'webhooks/%s/%s'
    );
    
    /**
     * Available Permissions in Discord.
     */
    const PERMISSIONS = array(
        'CREATE_INSTANT_INVITE' => 1 << 0,
        'KICK_MEMBERS' => 1 << 1,
        'BAN_MEMBERS' => 1 << 2,
        'ADMINISTRATOR' => 1 << 3,
        'MANAGE_CHANNELS' => 1 << 4,
        'MANAGE_GUILD' => 1 << 5,
        'ADD_REACTIONS' => 1 << 6,
        'VIEW_AUDIT_LOG' => 1 << 7,

        'VIEW_CHANNEL' => 1 << 10,
        'SEND_MESSAGES' => 1 << 11,
        'SEND_TTS_MESSAGES' => 1 << 12,
        'MANAGE_MESSAGES' => 1 << 13,
        'EMBED_LINKS' => 1 << 14,
        'ATTACH_FILES' => 1 << 15,
        'READ_MESSAGE_HISTORY' => 1 << 16,
        'MENTION_EVERYONE' => 1 << 17,
        'USE_EXTERNAL_EMOJIS' => 1 << 18,

        'CONNECT' => 1 << 20,
        'SPEAK' => 1 << 21,
        'MUTE_MEMBERS' => 1 << 22,
        'DEAFEN_MEMBERS' => 1 << 23,
        'MOVE_MEMBERS' => 1 << 24,
        'USE_VAD' => 1 << 25,

        'CHANGE_NICKNAME' => 1 << 26,
        'MANAGE_NICKNAMES' => 1 << 27,
        'MANAGE_ROLES' => 1 << 28,
        'MANAGE_WEBHOOKS' => 1 << 29,
        'MANAGE_EMOJIS' => 1 << 30
    );
    
    /**
     * Formats Endpoints strings.
     * @param  string  $endpoint
     * @param  string  $args
     * @return string
     * @access private
     */
    static function format(string $endpoint, ...$args) {
        return sprintf($endpoint, ...$args);
    }
}
