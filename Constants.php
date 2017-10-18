<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website => https://charuru.moe
 * License => MIT
*/

namespace CharlotteDunois\Yasmin;

/**
 * Holds all constants.
 */
class Constants {
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
    
    const CDN = array(
        'url' => 'https://cdn.discordapp.com/',
        'emojis' => 'emojis/%s.png',
        'icons' => 'icons/%s/%s.png',
        'splashs' => 'splashes/%s/%s.png',
        'defaultavatars' => 'embed/avatars/%s.png',
        'avatars' => 'avatars/%s/%s.%s',
        'appicons' => 'app-icons/%s/%s.png'
    );
    
    const HTTP = array(
        'url' => 'https://discordapp.com/api/',
        'version' => 7,
        'invite' => 'https://discord.gg'
    );
    
    const WS = array(
        'baseurl' => 'wss://gateway.discord.gg/',
        'encoding' => 'json',
        'version' => 6,
        'url' => ''
    );
    
    const CHANNEL_TYPE = array(
        0 => 'text',
        1 => 'dm',
        2 => 'voice',
        3 => 'group',
        4 => 'category',
    );
    
    const GAME_TYPES = array(
        0 => 'Playing',
        1 => 'Streaming'
    );
    
    const ENDPOINTS = array(
        'channels' => array(
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
                'bulkDelete' => 'channels/%s/messages/bulk-delete',
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
            )
        ),
        'emojis' => array(
            'list' => 'guilds/%s/emojis',
            'get' => 'guilds/%s/emojis/%s',
            'create' => 'guilds/%s/emojis',
            'modify' => 'guilds/%s/emojis/%s',
            'delete' => 'guilds/%s/emojis/%s'
        ),
        'guilds' => array(
            'get' => 'guilds/%s',
            'modify' => 'guilds/%s',
            'delete' => 'guilds/%s',
            'getChannels' => 'guilds/%s/channels',
            'createChannel' => 'guilds/%s/channels',
            'modifyChannelPosition' => 'guilds/%s/channels',
            'members' => array(
                'get' => 'guilds/%s/members/%s',
                'list' => 'guilds/%s/members',
                'addGuildMember' => 'guilds/%s/members/%s',
                'modifyGuildMember' => 'guilds/%s/members/%s',
                'modifyCurrentGuildMemberNick' => 'guilds/%s/members/@me/nick',
                'addRole' => 'guilds/%s/members/%s/roles/%s',
                'removeRole' => 'guilds/%s/members/%s/roles/%s',
                'removeMember' => 'guilds/%s/members/%s',
            ),
            'bans' => array(
                'getBans' => 'guilds/%s/bans',
                'createBan' => 'guilds/%s/bans/%s',
                'removeBan' => 'guilds/%s/bans/%s'
            ),
            'roles' => array(
                'getRoles' => 'guilds/%s/roles',
                'createRole' => 'guilds/%s/roles',
                'modifyRolePositions' => 'guilds/%s/roles',
                'modifyRole' => 'guilds/%s/roles/%s',
                'deleteRole' => 'guilds/%s/roles/%s'
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
        ),
        'invites' => array(
            'get' => 'invites/%s',
            'delete' => 'invites/%s',
            'accept' => 'invites/%s'
        ),
        'users' => array(
            'getUser' => 'users/%s',
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
        ),
        'voice' => array(
            'regions' => 'voice/regions'
        )
    );
    
    static function format(string $endpoint, ...$args) {
        return sprintf($endpoint, ...$args);
    }
}
