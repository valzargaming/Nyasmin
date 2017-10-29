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
 */
class PermissionOverwrite extends ClientBase { //TODO: Implementation
    protected $channel;
    
    protected $id;
    protected $type;
    protected $target;
    protected $allow;
    protected $deny;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel, array $permission) {
        parent::__construct($client);
        $this->channel = $channel;
        
        $this->id = $permission['id'];
        $this->type = $permission['type'] ?? $this->type;
        $this->target = ($this->type === 'role' ? $this->channel->guild->roles->get($permission['id']) : $this->channel->guild->members->get($permission['id']));
        $this->allow = new \CharlotteDunois\Yasmin\Models\Permissions(($permission['allow'] ?? 0));
        $this->deny = new \CharlotteDunois\Yasmin\Models\Permissions(($permission['deny'] ?? 0));
    }
    
    /**
     * @property-read  string                                                                                $id        The ID of the Permission Overwrite.
     * @property-read  string                                                                                $type      The type of the overwrite (role or user).
     * @property-read  \CharlotteDunois\Yasmin\Models\Role|\CharlotteDunois\Yasmin\Models\GuildMember|null   $target    The role or guildmember, or null if uncached.
     * @property-read  \CharlotteDunois\Yasmin\Models\Permissions                                            $allow     The allowed Permissions object.
     * @property-read  \CharlotteDunois\Yasmin\Models\Permissions                                            $deny      The denied Permissions object.
     *
     * @property-read  \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface                              $channel   The channel this Permission Overwrite belongs to.
     * @property-read  \CharlotteDunois\Yasmin\Models\Guild                                                  $guild     The guild this Permission Overwrite belongs to.
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
        
        return null;
    }
    
    /**
     * @internal
     */
    function JsonSerialize() {
        return array(
            'type' => $this->type,
            'target' => $this->id,
            'allow' => $this->allow,
            'deny' => $this->deny
        );
    }
}
