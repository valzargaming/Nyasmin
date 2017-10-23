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
    protected $bitfield;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, int $permission) {
        parent::__construct($client);
        
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
     * @param array|string|int  $permissions
     * @param boolean           $checkAdmin
     * @return boolean
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
     * @param array|string|int  $permissions
     * @param boolean           $checkAdmin
     * @return boolean
     */
    function missing($permissions, bool $checkAdmin = true) {
        return !$this->has($permissions, $checkAdmin);
    }
    
    /**
     * Resolves a permission name to number. Also checks if a given integer is a valid permission.
     * @param int|string  $permission
     * @return int
     * @throws \Exception
     */
    static function resolve($permission) {
        if(\is_int($permission) && \array_search($permission, \CharlotteDunois\Yasmin\Constants::PERMISSIONS, true) !== false) {
            return $permission;
        } elseif(\is_string($permission) && isset(\CharlotteDunois\Yasmin\Constants::PERMISSIONS[$permission])) {
            return \CharlotteDunois\Yasmin\Constants::PERMISSIONS[$permission];
        }
        
        throw new \Exception('Can not resolve unknown permission');
    }
}
