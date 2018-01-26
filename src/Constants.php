<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin;

/**
 * Holds all constants.
 */
final class Constants {
    /**
     * The version of Yasmin.
     * @var string
     */
    const VERSION = '0.2.2';
    
    /**
     * The default HTTP user agent.
     * @var string
     * @internal
     */
    const DEFAULT_USER_AGENT = 'Yasmin (https://github.com/CharlotteDunois/Yasmin)';
    
    /**
     * WS OP codes.
     * @var array
     * @internal
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
     * @var array
     * @internal
     */
    const CDN = array(
        'url' => 'https://cdn.discordapp.com/',
        'emojis' => 'emojis/%s.%s',
        'icons' => 'icons/%s/%s.%s',
        'splashes' => 'splashes/%s/%s.%s',
        'defaultavatars' => 'embed/avatars/%s.png',
        'avatars' => 'avatars/%s/%s.%s',
        'appicons' => 'app-icons/%s/%s.png',
        'appassets' => 'app-assets/%s/%s.png'
    );
    
    /**
     * HTTP constants.
     * @var array
     * @internal
     */
    const HTTP = array(
        'url' => 'https://discordapp.com/api/',
        'version' => 7,
        'invite' => 'https://discord.gg/'
    );
    
    /**
     * WS constants. Query string parameters.
     * @var array
     * @internal
     */
    const WS = array(
        'v' => 6,
        'encoding' => 'json'
    );
    
    /**
     * WS Close codes.
     * @var array
     * @internal
     */
    const WS_CLOSE_CODES = array(
        4004 => 'Tried to identify with an invalid token',
        4010 => 'Sharding data provided was invalid',
        4011 => 'Shard would be on too many guilds if connected',
        4012 => 'Invalid gateway version'
    );
    
    /**
     * WS connection status: Disconnected.
     * @var int
     */
    const WS_STATUS_DISCONNECTED = 0;
    
    /**
     * WS connection status: Connecting.
     * @var int
     */
    const WS_STATUS_CONNECTING = 1;
    
    /**
     * WS connection status: Reconnecting.
     * @var int
     */
    const WS_STATUS_RECONNECTING = 2;
    
    /**
     * WS connection status: Connected (not ready yet - nearly).
     * @var int
     */
    const WS_STATUS_NEARLY = 3;
    
    /**
     * WS connection status: Connected (ready).
     * @var int
     */
    const WS_STATUS_CONNECTED = 4;
    
    /**
     * WS connection status: Idling (disconnected and no reconnect planned).
     * @var int
     */
    const WS_STATUS_IDLE = 5;
    
    /**
     * WS default compression.
     * @var string
     */
    const WS_DEFAULT_COMPRESSION = 'zlib-stream';
    
    /**
     * Activity types.
     * @var array
     * @source
     */
    const ACTIVITY_TYPES = array(
        0 => 'playing',
        1 => 'streaming',
        2 => 'listening',
        3 => 'watching'
    );
    
    /**
     * Channel Types.
     * @var array
     * @source
     */
    const CHANNEL_TYPES = array(
        0 => 'text',
        1 => 'dm',
        2 => 'voice',
        3 => 'group',
        4 => 'category',
        
        'text' => 0,
        'dm' => 1,
        'voice' => 2,
        'group' => 3,
        'category' => 4
    );
    
    /**
     * Messages Types.
     * @var array
     * @source
     */
    const MESSAGE_TYPES = array(
        0 => 'DEFAULT',
        1 => 'RECIPIENT_ADD',
        2 => 'RECIPIENT_REMOVE',
        3 => 'CALL',
        4 => 'CHANNEL_NAME_CHANGE',
        5 => 'CHANNEL_ICON_CHANGE',
        6 => 'CHANNEL_PINNED_MESSAGE',
        7 => 'GUILD_MEMBER_JOIN'
    );
    
    /**
     * Guild default message notifications.
     * @var array
     * @source
     */
    const GUILD_DEFAULT_MESSAGE_NOTIFICATIONS = array(
        0 => 'EVERYTHING',
        1 => 'ONLY_MENTIONS'
    );
    
    /**
     * Guild explicit content filter.
     * @var array
     * @source
     */
    const GUILD_EXPLICIT_CONTENT_FILTER = array(
        0 => 'DISABLED',
        1 => 'MEMBERS_WITHOUT_ROLES',
        2 => 'ALL_MEMBERS'
    );
    
    /**
     * Guild MFA level.
     * @var array
     * @source
     */
    const GUILD_MFA_LEVEL = array(
        0 => 'NONE',
        1 => 'ELEVATED'
    );
    
    /**
     * Guild verification level.
     * @var array
     * @source
     */
    const GUILD_VERIFICATION_LEVEL = array(
        0 => 'NONE',
        1 => 'LOW',
        2 => 'MEDIUM',
        3 => 'HIGH',
        4 => 'VERY_HIGH'
    );
    
    /**
     * The default discord role colors. Mapped by uppercase string to integer.
     * @var array
     * @source
     */
    const DISCORD_COLORS = array(
        'AQUA' => 1752220,
        'BLUE' => 3447003,
        'GREEN' => 3066993,
        'PURPLE' => 10181046,
        'GOLD' => 15844367,
        'ORANGE' => 15105570,
        'RED' => 15158332,
        'GREY' => 9807270,
        'DARKER_GREY' => 8359053,
        'NAVY' => 3426654,
        'DARK_AQUA' => 1146986,
        'DARK_GREEN' => 2067276,
        'DARK_BLUE' => 2123412,
        'DARK_GOLD' => 12745742,
        'DARK_PURPLE' => 7419530,
        'DARK_ORANGE' => 11027200,
        'DARK_GREY' => 9936031,
        'DARK_RED' => 10038562,
        'LIGHT_GREY' => 12370112,
        'DARK_NAVY' => 2899536
    );
    
    /**
     * Endpoints General.
     * @var array
     * @internal
     */
    const ENDPOINTS_GENERAL = array(
        'currentOAuthApplication' => 'oauth2/applications/@me'
    );
    
    /**
     * Endpoints Channels.
     * @var array
     * @internal
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
     * @var array
     * @internal
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
     * @var array
     * @internal
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
     * @var array
     * @internal
     */
    const ENDPOINTS_INVITES = array(
        'get' => 'invites/%s',
        'delete' => 'invites/%s',
        'accept' => 'invites/%s'
    );
    
    /**
     * Endpoints Users.
     * @var array
     * @internal
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
     * @var array
     * @internal
     */
    const ENDPOINTS_VOICE = array(
        'regions' => 'voice/regions'
    );
    
    /**
     * Endpoints Webhooks.
     * @var array
     * @internal
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
     * @var array<string, int>
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
     * @internal
     */
    static function format(string $endpoint, ...$args) {
        return \sprintf($endpoint, ...$args);
    }
}
