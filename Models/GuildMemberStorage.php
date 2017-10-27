<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * @access private
 */
class GuildMemberStorage extends Collection
    implements \CharlotteDunois\Yasmin\Interfaces\StorageInterface { //TODO: Docs
    
    protected $client;
    protected $guild;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, $guild, array $data = null) {
        parent::__construct($data);
        $this->client = $client;
        $this->guild = $guild;
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
        }
        
        return null;
    }
    
    function resolve($guildmember) {
        if($guildmember instanceof \CharlotteDunois\Yasmin\Models\GuildMember) {
            return $guildmember;
        }
        
        if(\is_string($guildmember) && $this->has($guildmember)) {
            return $this->get($guildmember);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown guild member');
    }
    
    function factory(array $data) {
        $member = new \CharlotteDunois\Yasmin\Models\GuildMember($this->client, $this->guild, $data);
        $this->set($member->id, $member);
        return $member;
    }
}
