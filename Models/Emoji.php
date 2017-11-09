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
class Emoji extends ClientBase {
    protected $guild;
    
    protected $id;
    protected $name;
    protected $roles;
    protected $user;
    protected $requireColons;
    protected $managed;
    
    protected $createdTimestamp;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild = null, array $emoji) {
        parent::__construct($client);
        
        $this->id = (!empty($emoji['id']) ? $emoji['id'] : null);
        $this->createdTimestamp = ($this->id ? (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp : null);
        
        $this->guild = ($this->id ? $guild : null);
        $this->roles = new \CharlotteDunois\Yasmin\Utils\Collection();
        
        $this->_patch($emoji);
    }
    
    /**
     * @inheritDoc
     *
     * @property-read string|null                                          $id                 The emoji ID.
     * @property-read string                                               $name               The emoji name.
     * @property-read \CharlotteDunois\Yasmin\Models\User|null             $user               The user that created the emoji.
     * @property-read \CharlotteDunois\Yasmin\Models\Guild|null            $guild              The guild this emoji belongs to, or null.
     * @property-read boolean                                              $requireColons      Does the emoji require colons?
     * @property-read boolean                                              $managed            Is the emoji managed?
     * @property-read \CharlotteDunois\Yasmin\Utils\Collection             $roles              A collection of roles that this emoji is active for (empty if all).
     * @property-read int|null                                             $createdTimestamp   The timestamp of when this emoji was created.
     *
     * @property-read \DateTime|null                                       $createdAt          An DateTime object of the createdTimestamp.
     * @property-read string                                               $identifier         The identifier for the emoji.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                if($this->id) {
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
                }
                
                return null;
            break;
            case 'identifier':
                if($this->id) {
                    return $this->name.':'.$this->id;
                }
                
                return \rawurlencode($this->name);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Adds a role to the list of roles that can use this emoji.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role
     * @return \React\Promise\Promise<this>
     * @throws \BadMethodCallException
     */
    function addRestrictedRole($role) {
        $roles = $this->roles->map(function ($role) {
            return $role->id;
        })->all();
        $roles[] = $role;
        
        return $this->edit(array('roles' => \array_values($roles)));
    }
    
    /**
     * Adds multiple roles to the list of roles that can use this emoji.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role
     * @return \React\Promise\Promise<this>
     * @throws \BadMethodCallException
     */
    function addRestrictedRoles(...$role) {
        $roles = $this->roles->map(function ($role) {
            return $role->id;
        })->all();
        
        foreach($role as $r) {
            $roles[] = ($r instanceof \CharlotteDunois\Yasmin\Models\Role ? $r->id : $r);
        }
        
        return $this->edit(array('roles' => \array_values($roles)));
    }
    
    /**
     * Edits the emoji. Options are as following (at least one required):
     *
     *  array(
     *      'name' => string,
     *      'roles' => array<string|\CharlotteDunois\Yasmin\Models\Role>|\CharlotteDunois\Yasmin\Utils\Collection<string|\CharlotteDunois\Yasmin\Models\Role>
     *  )
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \BadMethodCallException
     */
    function edit(array $options, string $reason = '') {
        if($this->id === null) {
            throw new \BadMethodCallException('Can not edit a non-guild emoji');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($options, $reason) {
            if(!empty($options['roles'])) {
                if($options['roles'] instanceof \CharlotteDunois\Yasmin\Utils\Collection) {
                    $options['roles'] = $options['roles']->all();
                }
                
                $options['roles'] = \array_map(function ($role) {
                    if($role instanceof \CharlotteDunois\Yasmin\Models\Role) {
                        return $role->id;
                    }
                    
                    return $role;
                }, $options['roles']);
                
                $this->client->apimanager()->endpoints->emoji->modifyGuildEmoji($this->guild->id, $this->id, $options, $reason)->then(function () use ($resolve) {
                    $resolve($this);
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }
        }));
    }
    
    /**
     * Deletes the emoji.
     * @param string  $reason
     * @return \React\Promise\Promise<void>
     * @throws \BadMethodCallException
     */
    function delete(string $reason = '') {
        if($this->id === null) {
            throw new \BadMethodCallException('Can not delete a non-guild emoji');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->emoji->deleteGuildEmoji($this->guild->id, $this->id, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Removes a role from the list of roles that can use this emoji.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role
     * @return \React\Promise\Promise<this>
     * @throws \BadMethodCallException
     */
    function removeRestrictedRole($role) {
        if($this->roles->count() === 0) {
            return \React\Promise\resolve($this);
        }
        
        $roles = $this->roles->map(function ($role) {
            return $role->id;
        })->all();
        
        $key = \array_search(($role instanceof \CharlotteDunois\Yasmin\Models\Role ? $role->id : $role), $roles, true);
        if($key !== false) {
            unset($roles[$key]);
        }
        
        return $this->edit(array('roles' => $roles));
    }
    
    /**
     * Removes multiple roles from the list of roles that can use this emoji.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role
     * @return \React\Promise\Promise<this>
     * @throws \BadMethodCallException
     */
    function removeRestrictedRoles(...$role) {
        if($this->roles->count() === 0) {
            return \React\Promise\resolve($this);
        }
        
        $roles = $this->roles->map(function ($role) {
            return $role->id;
        })->all();
        
        foreach($role as $r) {
            $id = ($r instanceof \CharlotteDunois\Yasmin\Models\Role ? $r->id : $r);
            $key = \array_search($roles, $id, true);
            if($key !== false) {
                unset($roles[$key]);
            }
        }
        
        return $this->edit(array('roles' => $roles));
    }
    
    /**
     * Sets the new name of the emoji.
     * @param string  $name
     * @param string  $reason
     * @return \React\Promise\Promise<this>
     * @throws \BadMethodCallException
     */
    function setName(string $name, string $reason = '') {
        return $this->edit(array('name' => $name), $reason);
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
    
    /**
     * @internal
     */
    function _patch(array $emoji) {
        $this->name = $emoji['name'];
        $this->user = (!empty($emoji['user']) ? $this->client->users->patch($emoji['user']) : null);
        $this->requireColons = $emoji['require_colons'] ?? true;
        $this->managed = $emoji['managed'] ?? false;
        
        if(!empty($emoji['roles'])) {
            $this->roles->clear();
            
            foreach($emoji['roles'] as $role) {
                $this->roles->set($role['id'], $this->guild->roles->get($role['id']));
            }
        }
        
        $this->client->emojis->set($this->id ?? $this->name, $this);
    }
}
