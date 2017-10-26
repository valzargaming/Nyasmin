<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Permissions. Something fabulous.
 */
class Permissions extends Structure { //TODO: Docs
    const ALL = 2146958591;
    protected $bitfield;
    
    /**
     * @param int  $permission
     */
    function __construct(int $permission) {
        $this->bitfield = $permission;
    }
    
    /**
     * @property-read int  $bitfield  The bitfield value.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
    
    /**
     * Checks if a given permission is granted.
     * @param array|int|string  $permissions
     * @param boolean           $checkAdmin
     * @return boolean
     * @throws \InvalidArgumentException
     */
    function has($permissions, bool $checkAdmin = true) {
        if(!\is_array($permissions)) {
            $permissions = array($permissions);
        }
        
        if($checkAdmin && ($this->bitfield & \CharlotteDunois\Yasmin\Constants::PERMISSIONS['ADMINISTRATOR']) > 0) {
            return true;
        }
        
        foreach($permissions as $perm) {
            $perm = self::resolve($perm);
            if(($this->bitfield & $perm) !== $perm) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks if a given permission is missing.
     * @param array|int|string  $permissions
     * @param boolean           $checkAdmin
     * @return boolean
     * @throws \InvalidArgumentException
     */
    function missing($permissions, bool $checkAdmin = true) {
        return !$this->has($permissions, $checkAdmin);
    }
    
    /**
     * Adds permissions to these ones.
     * @param int|string  $permissions
     * @return this
     * @throws \InvalidArgumentException
     */
    function add(...$permissions) {
        $total = 0;
        foreach($permissions as $perm) {
            $perm = self::resolve($perm);
            $total |= $perm;
        }
        
        $this->bitfield |= $perm;
        return $this;
    }
    
    /**
     * Removes permissions from these ones.
     * @param int|string  $permissions
     * @return this
     * @throws \InvalidArgumentException
     */
    function remove(...$permissions) {
        $total = 0;
        foreach($permissions as $perm) {
            $perm = self::resolve($perm);
            $total |= $perm;
        }
        
        $this->bitfield &= ~$perm;
        return $this;
    }
    
    /**
     * Resolves a permission name to number. Also checks if a given integer is a valid permission.
     * @param int|string  $permission
     * @return int
     * @throws \InvalidArgumentException
     */
    static function resolve($permission) {
        if(\is_int($permission) && \array_search($permission, \CharlotteDunois\Yasmin\Constants::PERMISSIONS, true) !== false) {
            return $permission;
        } elseif(\is_string($permission) && isset(\CharlotteDunois\Yasmin\Constants::PERMISSIONS[$permission])) {
            return \CharlotteDunois\Yasmin\Constants::PERMISSIONS[$permission];
        }
        
        throw new \InvalidArgumentException('Can not resolve unknown permission');
    }
}
