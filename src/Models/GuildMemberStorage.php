<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Guild Member Storage to store guild members, utilizes Collection.
 */
class GuildMemberStorage extends Storage {
    protected $guild;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, ?array $data = null) {
        parent::__construct($client, $data);
        $this->guild = $guild;
    }
    
    /**
     * Resolves given data to a guildmember.
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|\CharlotteDunois\Yasmin\Models\User|string  string = user ID
     * @return \CharlotteDunois\Yasmin\Models\GuildMember
     * @throws \InvalidArgumentException
     */
    function resolve($guildmember) {
        if($guildmember instanceof \CharlotteDunois\Yasmin\Models\GuildMember) {
            return $guildmember;
        }
        
        if($guildmember instanceof \CharlotteDunois\Yasmin\Models\User) {
            $guildmember = $guildmember->id;
        }
        
        if(\is_int($guildmember)) {
            $guildmember = (string) $guildmember;
        }
        
        if(\is_string($guildmember) && $this->has($guildmember)) {
            return $this->get($guildmember);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown guild member');
    }
    
    /**
     * @internal
     */
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
