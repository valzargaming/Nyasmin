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
 * Represents a guild audit log entry.
 */
class GuildAuditLogEntry extends ClientBase {
    protected $log;
    
    protected $id;
    protected $changes;
    protected $userID;
    protected $actionType;
    protected $reason;
    
    protected $createdTimestamp;
    protected $extra;
    protected $target;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\GuildAuditLog $log, array $entry) {
        parent::__construct($client);
        $this->log = $log;
        
        $actionTypes = self::getActionTypes();
        
        $this->id = $entry['id'];
        $this->changes = $entry['changes'] ?? array();
        $this->userID = $entry['user_id'];
        $this->actionType = \array_search($entry['action_type'], $actionTypes, true);
        $this->reason = $entry['reason'] ?? null;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        
        if(!empty($entry['options'])) {
            if($this->actionType === $actionTypes['MEMBER_PRUNE']) {
                $this->extra = array(
                    'removed' => $entry['options']['members_removed'],
                    'days' => $entry['options']['delete_member_days']
                );
            } elseif($this->actionType === $actionTypes['MESSAGE_DELETE']) {
                $this->extra = array(
                    'count' => $entry['options']['count'],
                    'channel' => $this->client->channels->get($entry['options']['channel_id'])
                );
            } elseif(!empty($entry['options']['type'])) {
                switch($entry['options']['type']) {
                    case 'member':
                        $this->extra = $this->log->guild->members->get($entry['options']['id']);
                        if($this->extra === null) {
                            $this->extra = array('id' => $entry['options']['id']);
                        }
                    break;
                    case 'role':
                        $this->extra = $this->log->guild->roles->get($entry['options']['id']);
                        if($this->extra === null) {
                            $this->extra = array('id' => $entry['options']['id']);
                        }
                    break;
                }
            }
        }
        
        $targets = self::getTargetTypes();
        $targetType = self::getTargetType($entry['action_type']);
        
        if($targetType === $targets['UNKNOWN']) {
            $this->target = \array_reduce($this->changes, function ($carry,  $el) {
                $carry[$el['key']] = $el['new'] ?? $el['old'] ?? null;
                return $carry;
            }, array());
            $this->target['id'] = $entry['target_id'] ?? null;
        } elseif($targetType === $targets['USER'] || $targetType === $targets['GUILD']) {
            $method = \strtolower($targetType).'s';
            $this->target = $this->client->$method->get($entry['target_id']);
        } elseif($targetType === $targets['WEBHOOK']) {
            $this->target = $this->log->webhooks->get($entry['target_id']);
        } elseif($targetType === $targets['INVITE']) {
            if($this->log->guild->me->permissions->has(\CharlotteDunois\Yasmin\Constants::PERMISSIONS['MANAGE_GUILD'])) {
                $change = null;
                
                foreach($this->changes as $change) {
                    if($change['key'] === 'code') {
                        $change = $change['new'] ?? $change['old'] ?? null;
                        break;
                    }
                }
                
                if($change !== null) {
                    $this->target = $this->log->guild->fetchInvites()->then(function ($invites) use ($change) {
                        return $invites->first(function ($invite) use ($change) {
                            return ($invite->code === $change);
                        });
                    });
                }
            } else {
                $this->target = \array_reduce($this->changes, function ($el, $carry) {
                    $carry[$el['key']] = $el['new'] ?? $el['old'] ?? null;
                    return $carry;
                }, array());
            }
        } elseif($targetType === $targets['MESSAGE']) {
            $this->target = $this->client->users->get($entry['target_id']);
        } else {
            $method = \strtolower($targetType).'s';
            $this->target = $this->log->guild->$method->get($entry['target_id']);
        }
    }
    
    /**
     * @inheritDoc
     *
     * @property-read \CharlotteDunois\Yasmin\Models\GuildAuditLog  $log               The guild audit log which this entry belongs to.
     * @property-read string                                        $id                The ID of the audit log.
     * @property-read array[]                                       $changes           Specific property changes.
     * @property-read string                                        $userID            The ID of the user which triggered the audit log.
     * @property-read string                                        $actionType        Specific action type of this entry in its string presentation.
     * @property-read string|null                                   $reason            The specified reason, or null.
     * @property-read int                                           $createdTimestamp  When this audit log entry was created.
     * @property-read mixed|null                                    $extra             Any extra data from the entry, or null.
     * @property-read mixed|null                                    $target            The target of this entry, or null.
     *
     * @property-read \DateTime                                     $createdAt         The DateTime object of createdTimestamp.
     * @property-read \CharlotteDunois\Yasmin\Models\User|null      $user              The user which triggered the audit log.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'user':
                return $this->client->users->get($this->userID);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Finds the action type from the entry action.
     * @param int  $actionType
     * @return string
     */
    static function getActionType(int $actionType) {
        $actionTypes = self::getActionTypes();
        
        if(\in_array($actionType, array(
            $actionTypes['CHANNEL_CREATE'],
            $actionTypes['CHANNEL_OVERWRITE_CREATE'],
            $actionTypes['EMOJI_CREATE'],
            $actionTypes['INVITE_CREATE'],
            $actionTypes['MEMBER_BAN_REMOVE'],
            $actionTypes['ROLE_CREATE'],
            $actionTypes['WEBHOOK_CREATE']
        ))) {
            return 'CREATE';
        }
        
        if(\in_array($actionType, array(
            $actionTypes['CHANNEL_DELETE'],
            $actionTypes['CHANNEL_OVERWRITE_DELETE'],
            $actionTypes['EMOJI_DELETE'],
            $actionTypes['INVITE_DELETE'],
            $actionTypes['MEMBER_BAN_ADD'],
            $actionTypes['MEMBER_KICK'],
            $actionTypes['MEMBER_PRUNE'],
            $actionTypes['MESSAGE_DELETE'],
            $actionTypes['ROLE_DELETE'],
            $actionTypes['WEBHOOK_DELETE']
        ))) {
            return 'DELETE';
        }
        
        if(\in_array($actionType, array(
            $actionTypes['CHANNEL_UPDATE'],
            $actionTypes['CHANNEL_OVERWRITE_UPDATE'],
            $actionTypes['EMOJI_UPDATE'],
            $actionTypes['GUILD_UPDATE'],
            $actionTypes['INVITE_UPDATE'],
            $actionTypes['MEMBER_UPDATE'],
            $actionTypes['MEMBER_ROLE_UPDATE'],
            $actionTypes['ROLE_UPDATE'],
            $actionTypes['WEBHOOK_UPDATE']
        ))) {
            return 'UPDATE';
        }

        return 'ALL';
    }
    
    /**
     * All available actions keyed under their names to their numeric values.
     * @return string[]
     */
    static function getActionTypes() {
        return array(
            'ALL' => null,
            'GUILD_UPDATE' => 1,
            'CHANNEL_UPDATE' => 11,
            'CHANNEL_CREATE' => 10,
            'CHANNEL_OVERWRITE_CREATE' => 13,
            'CHANNEL_DELETE' => 12,
            'CHANNEL_OVERWRITE_UPDATE' => 14,
            'CHANNEL_OVERWRITE_DELETE' => 15,
            'MEMBER_KICK' => 20,
            'MEMBER_PRUNE' => 21,
            'MEMBER_BAN_ADD' => 22,
            'MEMBER_BAN_REMOVE' => 23,
            'MEMBER_UPDATE' => 24,
            'MEMBER_ROLE_UPDATE' => 25,
            'ROLE_CREATE' => 30,
            'ROLE_UPDATE' => 31,
            'ROLE_DELETE' => 32,
            'INVITE_CREATE' => 40,
            'INVITE_UPDATE' => 41,
            'INVITE_DELETE' => 42,
            'WEBHOOK_CREATE' => 50,
            'WEBHOOK_UPDATE' => 51,
            'WEBHOOK_DELETE' => 52,
            'EMOJI_CREATE' => 60,
            'EMOJI_UPDATE' => 61,
            'EMOJI_DELETE' => 62,
            'MESSAGE_DELETE' => 72
        );
    }
    /**
     * Finds the target type from the entry action.
     *
     * One of GUILD, CHANNEL, USER, ROLE, INVITE, WEBHOOK, EMOJI, MESSAGE or UNKNOWN.
     *
     * @param int $target
     * @return string
     */
    static function getTargetType(int $target) {
        $targets = self::getTargetTypes();
        
        if($target < 10) {
            return $targets['GUILD'];
        }
        if($target < 20) {
            return $targets['CHANNEL'];
        }
        if($target < 30) {
            return $targets['USER'];
        }
        if($target < 40) {
            return $targets['ROLE'];
        }
        if($target < 50) {
            return $targets['INVITE'];
        }
        if($target < 60) {
            return $targets['WEBHOOK'];
        }
        if($target < 70) {
            return $targets['EMOJI'];
        }
        if($target < 80) {
            return $targets['MESSAGE'];
        }
        
        return $targets['UNKNOWN'];
    }
    
    /**
     * Key mirror of all available audit log targets.
     * @return string[]
     */
    static function getTargetTypes() {
        return array(
            'ALL' => 'ALL',
            'GUILD' => 'GUILD',
            'CHANNEL' => 'CHANNEL',
            'USER' => 'USER',
            'ROLE' => 'ROLE',
            'INVITE' => 'INVITE',
            'WEBHOOK' => 'WEBHOOK',
            'EMOJI' => 'EMOJI',
            'MESSAGE' => 'MESSAGE',
            'UNKNOWN' => 'UNKNOWN'
        );
    }
}
