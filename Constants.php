<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website => https =>//charuru.moe
 * License => MIT
*/

namespace CharlotteDunois\Yasmin;

class Constants {
    static public $opcodes = array(
        'DISPATCH' => 0,
        'HEARTBEAT' => 1,
        'IDENTIFY' => 2,
        'PRESENCE' => 3,
        'VOICE_STATE_UPDATE' => 4,
        'VOICE_SERVER_PING' => 5,
        'RESUME' => 6,
        'RECONNECT' => 7,
        'REQUEST_GUILD_MEMBERS' => 8,
        'INVALIDATE_SESSION' => 9,
        'HELLO' => 10,
        'HEARTBEAT_ACK' => 11,
        'GUILD_SYNC' => 12
    );
    static public $opcodesNumber = array();
    
    static public $cdn;
    
    static public $http = array(
        'baseurl' => 'https://discordapp.com/api/',
        'url' => '',
        'version' => 6
    );
    
    static public $ws = array(
        'baseurl' => 'wss://gateway.discord.gg/',
        'encoding' => 'json',
        'version' => 6,
        'url' => ''
    );
    
    static public $endpoints = array(
        'users' => array(
            
        ),
    );
}

Constants::$opcodesNumber = array_flip(Constants::$opcodes);

Constants::$cdn = array(
    'url' => 'https://cdn.discordapp.com/',
    'emojis' => function ($id) {
        return 'emojis/'.$id.'.png';
    },
    'icons' => function ($id, $hash) {
        return 'icons/'.$id.'/'.$hash.'.png';
    },
    'splashs' => function ($id, $splash) {
        return 'splashes/'.$id.'/'.$splash.'.png';
    },
    'defaultavatars' => function ($modulo) {
        return 'embed/avatars/'.$modulo.'.png';
    },
    'avatars' => function ($id, $hash, $format) {
        if(empty($format)) {
            $format = (strpos($hash, 'a_') === 0 ? 'gif' : 'webp');
        }
        
        return 'avatars/'.$id.'/'.$hash.'.'.$format;
    },
    'appicons' => function ($id, $icon) {
        return 'app-icons/'.$id.'/'.$icon.'.png';
    }
);

Constants::$http['url'] = Constants::$http['baseurl'].Constants::$http['version'];

Constants::$ws['url'] = Constants::$ws['baseurl'].'?v='.Constants::$ws['version'].'&encoding='.Constants::$ws['encoding'];
