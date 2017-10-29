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
 * Represents a role.
 */
class Role extends ClientBase { //TODO: Implementation
    protected $guild;
    
    protected $id;
    protected $name;
    protected $color;
    protected $hoist;
    protected $position;
    protected $permissions;
    protected $managed;
    protected $mentionable;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $role) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->id = $role['id'];
        $this->name = $role['name'];
        $this->color = $role['color'];
        $this->hoist = $role['hoist'];
        $this->position = $role['position'];
        $this->permissions = new \CharlotteDunois\Yasmin\Models\Permissions($role['permissions']);
        $this->managed = $role['managed'];
        $this->mentionable = $role['mentionable'];
    }
    
    /**
     * @property-read string                                      $id            The role ID.
     * @property-read string                                      $name          The role name.
     * @property-read int                                         $color         The color of the role.
     * @property-read bool                                        $hoist         Whether the role gets displayed separately in the member list.
     * @property-read int                                         $position      The position in the role manager.
     * @property-read \CharlotteDunois\Yasmin\Models\Permissions  $permissions   The permissions of the role.
     * @property-read bool                                        $managed       Whether the role is managed by an integration.
     * @property-read bool                                        $mentionable   Whether the role is mentionable.
     *
     * @property-read \CharlotteDunois\Collect\Collection         $members       A collection of all (cached) guild members which have this role.
     * @property-read
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'members':
                return $this->guild->members->filter(function ($member) {
                    return $member->roles->has($this->id);
                });
            break;
        }
        
        return null;
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<@&'.$this->id.'>';
    }
}
