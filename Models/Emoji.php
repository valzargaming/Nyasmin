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
 * Represents an emoji.
 */
class Emoji extends ClientBase { //TODO: Implementation
    protected $guild;
    
    protected $id;
    protected $name;
    protected $roles;
    protected $user;
    protected $requireColons;
    protected $managed;
    
    protected $createdTimestamp;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild = null, array $emoji) {
        parent::__construct($client);
        $this->guild = $guild;
        
        $this->id = (!empty($emoji['id']) ? $emoji['id'] : null);
        $this->name = $emoji['name'];
        $this->user = (!empty($emoji['user']) ? $client->users->patch($emoji['user']) : null);
        $this->requireColons = $emoji['require_colons'] ?? true;
        $this->managed = $emoji['managed'] ?? false;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
        $this->roles = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        if(!empty($emoji['roles'])) {
            foreach($emoji['roles'] as $role) {
                $this->roles->set($role['id'], $this->guild->roles->get($role['id']));
            }
        }
        
        $client->emojis->set($this->id ?? $this->name, $this);
    }
    
    /**
     * @property-read string|null                                          $id                 The emoji ID.
     * @property-read string                                               $name               The emoji name.
     * @property-read \CharlotteDunois\Yasmin\Models\User|null         $user               The user that created the emoji.
     * @property-read boolean                                              $requireColons      Does the emoji require colons?
     * @property-read boolean                                              $managed            Is the emoji managed?
     * @property-read \CharlotteDunois\Yasmin\Utils\Collection        $roles              A collection of roles that this emoji is active for (empty if all).
     * @property-read int                                                  $createdTimestamp   The timestamp of when this emoji was created.
     *
     * @property-read \DateTime                                            $createdAt          An DateTime object of the createdTimestamp.
     * @property-read string                                               $identifier         The identifier for the emoji.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
            case 'identifier':
                if($this->id) {
                    return $this->name.':'.$this->id;
                }
                
                return \urlencode($this->name);
            break;
        }
        
        return null;
    }
    
    function edit(array $data) {
        
    }
    
    function delete(string $reason) {
        
    }
    
    function addRestrictedRoles(...$roles) {
        
    }
    
    function removeRestrictedRoles(...$roles) {
        
    }
    
    /**
     * Automatically converts to a mention.
     */
    function __toString() {
        if($this->requireColons === false) {
            return $this->name;
        }
        
        return '<:'.$this->name.':'.$this->id.'>';
    }
}
