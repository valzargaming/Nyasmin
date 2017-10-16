<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class GuildMemberStorage extends Collection { //TODO: Docs
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
    }
    
    function client() {
        return $this->client;
    }
    
    function resolve($guildmember) {
        if($guildmember instanceof \CharlotteDunois\Yasmin\Structures\GuildMember) {
            return $guildmember;
        }
        
        if(is_string($guildmember) && $this->has($guildmember)) {
            return $this->get($guildmember);
        }
        
        throw new \Exception('Unable to resolve unknown guild member');
    }
}
