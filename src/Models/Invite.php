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
 * Represents an invite.
 *
 * @property string                                                                                                  $code                The invite code.
 * @property \CharlotteDunois\Yasmin\Models\Guild|\CharlotteDunois\Yasmin\Models\PartialGuild|null                   $guild               The guild which this invite belongs to, or null.
 * @property \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface|\CharlotteDunois\Yasmin\Models\PartialChannel  $channel             The channel which this invite belongs to.
 * @property int|null                                                                                                $createdTimestamp    When this invite was created, or null.
 * @property \CharlotteDunois\Yasmin\Models\User|null                                                                $inviter             The inviter, or null.
 * @property int|null                                                                                                $maxUses             Maximum uses until the invite expires, or null.
 * @property int|null                                                                                                $maxAge              Duration (in seconds) until the invite expires, or null.
 * @property bool|null                                                                                               $revoked             If the invite is revoked, this will indicate it, or null.
 * @property bool|null                                                                                               $temporary           If this invite grants temporary membership, or null.
 * @property int|null                                                                                                $uses                Number of times this invite has been used, or null.
 * @property int|null                                                                                                $presenceCount       Approximate amount of presences, or null.
 * @property int|null                                                                                                $memberCount         Approximate amount of members, or null.
 *
 * @property \DateTime|null                                                                                          $createdAt           The DateTime instance of the createdTimestamp, or null.
 * @property string                                                                                                  $url                 Returns the URL for the invite.
 */
class Invite extends ClientBase {
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
    
    protected $presenceCount;
    protected $memberCount;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $invite) {
        parent::__construct($client);
        
        $this->code = $invite['code'];
        $this->guild = (!empty($invite['guild']) ? ($client->guilds->get($invite['guild']['id']) ?? (new \CharlotteDunois\Yasmin\Models\PartialGuild($client, $invite['guild']))) : null);
        $this->channel = ($client->channels->get($invite['channel']['id']) ?? (new \CharlotteDunois\Yasmin\Models\PartialChannel($client, $invite['channel'])));
        
        $this->createdTimestamp = (!empty($invite['created_at']) ? (new \DateTime($invite['created_at']))->getTimestamp() : null);
        $this->inviter = (!empty($invite['inviter']) ? $client->users->patch($invite['inviter']) : null);
        $this->maxUses = $invite['max_uses'] ?? null;
        $this->maxAge = $invite['max_age'] ?? null;
        $this->revoked = $invite['revoked'] ?? null;
        $this->temporary = $invite['temporary'] ?? null;
        $this->uses = $invite['uses'] ?? null;
        
        $this->presenceCount = (isset($invite['approximate_presence_count']) ? ((int) $invite['approximate_presence_count']) : $this->presenceCount);
        $this->memberCount = (isset($invite['approximate_member_count']) ? ((int) $invite['approximate_member_count']) : $this->memberCount);
    }
    
    /**
     * {@inheritdoc}
     * @return mixed
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                if($this->createdTimestamp !== null) {
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
                }
                
                return null;
            break;
            case 'url':
                return \CharlotteDunois\Yasmin\HTTP\APIEndpoints::HTTP['invite'].$this->code;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Deletes the invite.
     * @param string  $reason
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function delete(string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->invite->deleteInvite($this->code, $reason)->done(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
}
