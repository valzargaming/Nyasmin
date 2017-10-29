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
    
    protected $createdTimestamp;
    
    /**
     * @internal
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
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @property-read \CharlotteDunois\Yasmin\Models\Guild        $guild               The guild this role belongs to.
     * @property-read string                                      $id                  The role ID.
     * @property-read string                                      $name                The role name.
     * @property-read int                                         $createdTimestamp    When this role was creted.
     * @property-read int                                         $color               The color of the role.
     * @property-read bool                                        $hoist               Whether the role gets displayed separately in the member list.
     * @property-read int                                         $position            The position of the role in the API.
     * @property-read \CharlotteDunois\Yasmin\Models\Permissions  $permissions         The permissions of the role.
     * @property-read bool                                        $managed             Whether the role is managed by an integration.
     * @property-read bool                                        $mentionable         Whether the role is mentionable.
     *
     * @property-read int                                         $calculatedPosition  The role position in the role manager.
     * @property-read \DateTime                                   $createdAt           The DateTime object of createdTimestamp.
     * @property-read bool                                        $editable            Whether the role can be edited by the client user.
     * @property-read string                                      $hexColor            Returns the hex color of the role color.
     * @property-read \CharlotteDunois\Yasmin\Utils\Collection    $members             A collection of all (cached) guild members which have this role.
     * @property-read
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'calculatedPosition':
                $sorted = $this->guild->roles->sortByDesc(function ($role) {
                    return $role->position;
                });
                
                return $sorted->indexOf($this);
            break;
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'editable':
                if($this->managed) {
                    return false;
                }
                
                $member = $this->guild->me;
                if(!$member->permissions->has(\CharlotteDunois\Yasmin\Constants::PERMISSIONS['MANAGE_ROLES'])) {
                    return false;
                }
                
                return ($member->highestRole->comparePositionTo($this) > 0);
            break;
            case 'hexColor':
                return '#'.\dechex($this->color);
            break;
            case 'members':
                if($this->id === $this->guild->id) {
                    return $this->guild->members->copy();
                }
                
                return $this->guild->members->filter(function ($member) {
                    return $member->roles->has($this->id);
                });
            break;
        }
        
        return null;
    }
    
    /**
     * Compares the position from this role to the given role.
     * @param \CharlotteDunois\Yasmin\Models\Role  $role
     * @return int
     */
    function comparePositionTo(\CharlotteDunois\Yasmin\Models\Role $role) {
        if($this->position === $role->position) {
            return $role->id <=> $this->id;
        }
        
        return $this->position <=> $role->position;
    }
    
    function edit(array $options, string $reason = '') {
        
    }
    
    function delete(string $reason = '') {
        
    }
    
    /**
     * Set the color of this role.
     * @param mixed   $color
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor
     */
    function setColor($color, string $reason = '') {
        $color = \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveColor($color);
    }
    
    function setHoist(bool $hoist, string $reason = '') {
    
    }
    
    function setMentionable(bool $mentionable, string $reason = '') {
        
    }
    
    function setName(string $name, string $reason = '') {
        
    }
    
    function setPermissions($permissions, string $reason = '') {
        
    }
    
    function setPosition(int $position, string $reason = '') {
        
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        return '<@&'.$this->id.'>';
    }
}
