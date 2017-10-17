<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Permissions extends Structure { //TODO: Docs
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
    
    function __construct($client, $permission) {
        parent::__construct($client);
        
        $this->bitfield = $permission;
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
    
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
    
    function missing($permissions, bool $checkAdmin = true) {
        return !$this->has($permissions, $checkAdmin);
    }
    
    static function resolve($permission) {
        if(\is_int($permission) && \array_search($permission, self::FLAGS, true) !== false) {
            return $permission;
        } elseif(\is_string($permission) && isset(self::FLAGS[$permission])) {
            return self::FLAGS[$permission];
        }
        
        throw new \Exception('Can not resolve unknown permission');
    }
}
