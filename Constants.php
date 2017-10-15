<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website => https =>//charuru.moe
 * License => MIT
*/

namespace CharlotteDunois\NekoCord;

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
    
    static public $endpoints = array(
        
    );
}

Constants::$opcodesNumber = array_flip(Constants::$opcodes);
