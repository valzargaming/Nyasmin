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
 * Permissions. Something fabulous.
 */
class Permissions extends Structure { //TODO: Docs
    /**
     * Available Permissions in Discord.
     */
    const FLAGS = array(
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
    
    protected $bitfield;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, int $permission) {
        parent::__construct($client);
        
        $this->bitfield = $permission;
    }
    
    /**
     * @property-read int  $bitfield  The bitfield value.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
    
    /**
     * Checks if a given permission is granted.
     * @param array|string|int  $permissions
     * @param boolean           $checkAdmin
     * @return boolean
     */
    function has($permissions, bool $checkAdmin = true) {
        if(!\is_array($permissions)) {
            $permissions = array($permissions);
        }
        
        if($checkAdmin && ($this->bitfield & self::FLAGS['ADMINISTRATOR']) > 0) {
            return true;
        }
        
        foreach($permissions as $perm) {
            $perm = self::resolve($perm);
            if(($this->bitfield & $perm) !== $perm) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks if a given permission is missing.
     * @param array|string|int  $permissions
     * @param boolean           $checkAdmin
     * @return boolean
     */
    function missing($permissions, bool $checkAdmin = true) {
        return !$this->has($permissions, $checkAdmin);
    }
    
    /**
     * Resolves a permission name to number. Also checks if a given integer is a valid permission.
     * @param int|string  $permission
     * @return int
     * @throws \Exception
     */
    static function resolve($permission) {
        if(\is_int($permission) && \array_search($permission, self::FLAGS, true) !== false) {
            return $permission;
        } elseif(\is_string($permission) && isset(self::FLAGS[$permission])) {
            return self::FLAGS[$permission];
        }
        
        throw new \Exception('Can not resolve unknown permission');
    }
}
