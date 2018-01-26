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
 * Represents a permission overwrite.
 *
 * @property  string                                                                                $id        The ID of the Permission Overwrite.
 * @property  string                                                                                $type      The type of the overwrite (member or role).
 * @property  \CharlotteDunois\Yasmin\Models\Role|\CharlotteDunois\Yasmin\Models\GuildMember|null   $target    The role or guildmember, or null if not a member.
 * @property  \CharlotteDunois\Yasmin\Models\Permissions                                            $allow     The allowed Permissions instance.
 * @property  \CharlotteDunois\Yasmin\Models\Permissions                                            $deny      The denied Permissions instance.
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
    * Edits the permission overwrite. Resolves with $this.
    * @param \CharlotteDunois\Yasmin\Models\Permissions|int|null                                    $allow         Which permissions should be allowed?
    * @param \CharlotteDunois\Yasmin\Models\Permissions|int|null                                    $deny          Which permissions should be denied?
    * @param string                                                                                 $reason        The reason for this.
    * @return \React\Promise\Promise
    * @throws \InvalidArgumentException
     */
    function edit($allow, $deny = null, string $reason = '') {
        $options = array(
            'type' => $this->type
        );
        
        $allow = ($allow ?: $this->allow);
        $deny = ($deny ?: $this->deny);
        
        if($allow instanceof \CharlotteDunois\Yasmin\Models\Permissions) {
            $allow = $allow->bitfield;
        }
        
        if(!\is_int($allow)) {
            throw new \InvalidArgumentException('Allow has to be an int or an instance of Permissions');
        }
        
        if($deny instanceof \CharlotteDunois\Yasmin\Models\Permissions) {
            $deny = $deny->bitfield;
        }
        
        if(!\is_int($deny)) {
            throw new \InvalidArgumentException('Deny has to be an int or an instance of Permissions');
        }
        
        if($allow === $this->allow->bitfield && $deny === $this->deny->bitfield) {
            throw new \InvalidArgumentException('One of allow or deny has to be changed');
        }
        
        $options['allow'] = $allow;
        $options['deny'] = $deny;
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options, $reason) {
            $this->client->apimanager()->endpoints->channel->editChannelPermissions($this->channel->id, $this->id, $options, $reason)->then(function () use ($options, $resolve) {
                $this->allow = new \CharlotteDunois\Yasmin\Models\Permissions(($options['allow'] ?? 0));
                $this->deny = new \CharlotteDunois\Yasmin\Models\Permissions(($options['deny'] ?? 0));
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
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
