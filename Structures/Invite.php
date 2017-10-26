<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Represents an invite.
 */
class Invite extends Structure {
    protected $code;
    protected $guild;
    protected $channel;
    
    protected $createdTimestamp;
    protected $inviter;
    protected $maxUses;
    protected $maxAge;
    protected $revoked;
    protected $temporary;
    protected $uses;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $invite) {
        parent::__construct($client);
        
        $this->code = $invite['code'];
        $this->guild = ($client->guilds->get($invite['guild']['id']) ?? (new \CharlotteDunois\Yasmin\Structures\PartialGuild($client, $invite['guild'])));
        $this->channel = ($client->channels->get($invite['channel']['id']) ?? (new \CharlotteDunois\Yasmin\Structures\PartialChannel($client, $invite['channel'])));
        
        $this->createdTimestamp = (!empty($invite['created_at']) ? (new \DateTime($invite['created_at']))->format('U') : null);
        $this->inviter = (!empty($invite['inviter']) ? $client->users->patch($invite['inviter']) : null);
        $this->maxUses = $invite['max_uses'] ?? null;
        $this->maxAge = $invite['max_age'] ?? null;
        $this->revoked = $invite['revoked'] ?? null;
        $this->temporary = $invite['temporary'] ?? null;
        $this->uses = $invite['uses'] ?? null;
    }
    
    /**
     * @property-read string                                                                                                 $code                The invite code.
     * @property-read \CharlotteDunois\Yasmin\Structures\Guild|\CharlotteDunois\Yasmin\Structures\PartialGuild               $guild               The guild which this invite belongs to.
     * @property-read \CharlotteDunois\Yasmin\Interfaces\ChannelInterface|\CharlotteDunois\Yasmin\Structures\PartialChannel  $channel             The channel which this invite belongs to.
     * @property-read int|null                                                                                               $createdTimestamp    When this invite was created, or null.
     * @property-read \CharlotteDunois\Yasmin\Structures\User|null                                                           $inviter             The inviter, or null.
     * @property-read int|null                                                                                               $maxUses             Maximum uses until the invite expires, or null.
     * @property-read int|null                                                                                               $maxAge              Duration (in seconds) until the invite expires, or null.
     * @property-read bool|null                                                                                              $revoked             If the invite is revoked, this will indicate it, or null.
     * @property-read bool|null                                                                                              $temporary           If this invite grants temporary membership, or null.
     * @property-read int|null                                                                                               $uses                Number of times this invite has been used, or null.
     *
     * @property-read \DateTime|null                                                                                         $createdAt           The DateTime object of the createdTimestamp, if not null.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                if($this->createdTimestamp) {
                    return (new \DateTime('@'.$this->createdTimestamp));
                }
            break;
        }
        
        return null;
    }
}
