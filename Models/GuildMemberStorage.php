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
 * @internal
 * @todo Docs
 */
class GuildMemberStorage extends Storage {
    protected $guild;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $data = null) {
        parent::__construct($client, $data);
        $this->guild = $guild;
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
        if($this->has($data['user']['id'])) {
            $member = $this->get($data['user']['id']);
            $member->_patch($data);
            return $member;
        }
        
        $member = new \CharlotteDunois\Yasmin\Models\GuildMember($this->client, $this->guild, $data);
        $this->set($member->id, $member);
        return $member;
    }
}
