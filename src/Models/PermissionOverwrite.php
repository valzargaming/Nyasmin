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
 * Represents a permission overwrite.
 *
 * @property  string                                                                                $id        The ID of the Permission Overwrite.
 * @property  string                                                                                $type      The type of the overwrite (member or role).
 * @property  \CharlotteDunois\Yasmin\Models\Role|\CharlotteDunois\Yasmin\Models\GuildMember|null   $target    The role or guildmember, or null if uncached.
 * @property  \CharlotteDunois\Yasmin\Models\Permissions                                            $allow     The allowed Permissions object.
 * @property  \CharlotteDunois\Yasmin\Models\Permissions                                            $deny      The denied Permissions object.
 *
 * @property  \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface                              $channel   The channel this Permission Overwrite belongs to.
 * @property  \CharlotteDunois\Yasmin\Models\Guild                                                  $guild     The guild this Permission Overwrite belongs to.
 */
class PermissionOverwrite extends ClientBase {
    protected $channel;
    
    protected $id;
    protected $type;
    protected $target;
    protected $allow;
    protected $deny;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface $channel, array $permission) {
        parent::__construct($client);
        $this->channel = $channel;
        
        $this->id = $permission['id'];
        $this->type = $permission['type'];
        $this->target = ($this->type === 'role' ? $this->channel->guild->roles->get($permission['id']) : $this->channel->guild->members->get($permission['id']));
        $this->allow = new \CharlotteDunois\Yasmin\Models\Permissions(($permission['allow'] ?? 0));
        $this->deny = new \CharlotteDunois\Yasmin\Models\Permissions(($permission['deny'] ?? 0));
    }
    
    /**
     * @inheritDoc
     *
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'guild':
                return $this->channel->guild;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Deletes the permission overwrite.
     * @param string  $reason
     * @return \React\Promise\Promise
     */
    function delete(string $reason = '') {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->channel->deleteChannelPermission($this->channel->id, $this->id, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * @internal
     */
    function JsonSerialize() {
        return array(
            'type' => $this->type,
            'id' => $this->id,
            'allow' => $this->allow,
            'deny' => $this->deny
        );
    }
}
