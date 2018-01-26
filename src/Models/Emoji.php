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
 * Represents an emoji - both custom and unicode emojis.
 *
 * @property string|null                                          $id                 The emoji ID, or null for unicode emoji.
 * @property string                                               $name               The emoji name.
 * @property \CharlotteDunois\Yasmin\Models\User|null             $user               The user that created the emoji, or null.
 * @property \CharlotteDunois\Yasmin\Models\Guild|null            $guild              The guild this emoji belongs to, or null.
 * @property int|null                                             $createdTimestamp   The timestamp of when this emoji was created, or null for unicode emoji.
 * @property bool                                                 $animated           Whether this emoji is animated.
 * @property boolean                                              $managed            Is the emoji managed?
 * @property boolean                                              $requireColons      Does the emoji require colons?
 * @property \CharlotteDunois\Yasmin\Utils\Collection             $roles              A collection of roles that this emoji is active for (empty if all).
 *
 * @property \DateTime|null                                       $createdAt          An DateTime instance of the createdTimestamp, or null for unicode emoji.
 * @property string                                               $identifier         The identifier for the emoji.
 * @property string|null                                          $url                The URL to the emoji image, or null for unicode emoji.
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
    function __construct(\CharlotteDunois\Yasmin\Client $client, ?\CharlotteDunois\Yasmin\Models\Guild $guild = null, array $emoji) {
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
     * @throws \Exception
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                if($this->id !== null && $this->createdTimestamp !== null) {
                    return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
                }
                
                return null;
            break;
            case 'identifier':
                if($this->id !== null) {
                    return $this->name.':'.$this->id;
                }
                
                return \rawurlencode($this->name);
            break;
            case 'url':
                if($this->id !== null) {
                    return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['emojis'], $this->id, ($this->animated ? 'gif' : 'png'));
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Adds a role to the list of roles that can use this emoji. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
     */
    function addRestrictedRole($role) {
        $roles = $this->roles->map(function ($role) {
            return $role->id;
        })->all();
        $roles[] = $role;
        
        return $this->edit(array('roles' => \array_values($roles)));
    }
    
    /**
     * Adds multiple roles to the list of roles that can use this emoji. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  ...$role
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
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
     * Edits the emoji. Resolves with $this.
     *
     * Options are as following (at least one required):
     *
     * <pre>
     * array(
     *   'name' => string,
     *   'roles' => array<string|\CharlotteDunois\Yasmin\Models\Role>|\CharlotteDunois\Yasmin\Utils\Collection<string|\CharlotteDunois\Yasmin\Models\Role>
     * )
     * </pre>
     *
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
     */
    function edit(array $options, string $reason = '') {
        if($this->id === null) {
            throw new \BadMethodCallException('Unable to edit an unicode emoji');
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
            }
            
            $this->client->apimanager()->endpoints->emoji->modifyGuildEmoji($this->guild->id, $this->id, $options, $reason)->then(function () use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Deletes the emoji.
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
     */
    function delete(string $reason = '') {
        if($this->id === null) {
            throw new \BadMethodCallException('Unable to delete a non-guild emoji');
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($reason) {
            $this->client->apimanager()->endpoints->emoji->deleteGuildEmoji($this->guild->id, $this->id, $reason)->then(function () use ($resolve) {
                $resolve();
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Removes a role from the list of roles that can use this emoji. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  $role
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
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
     * Removes multiple roles from the list of roles that can use this emoji. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\Role|string  ...$role
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
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
            $key = \array_search($id, $roles, true);
            if($key !== false) {
                unset($roles[$key]);
            }
        }
        
        return $this->edit(array('roles' => $roles));
    }
    
    /**
     * Sets the new name of the emoji. Resolves with $this.
     * @param string  $name
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException  Throws on unicode emojis.
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
        
        return '<'.($this->animated ? 'a' : '').':'.$this->name.':'.$this->id.'>';
    }
    
    /**
     * @internal
     */
    function _patch(array $emoji) {
        $this->name = $emoji['name'];
        $this->user = (!empty($emoji['user']) ? $this->client->users->patch($emoji['user']) : null);
        $this->animated = (bool) ($emoji['animated'] ?? false);
        $this->managed = (bool) ($emoji['managed'] ?? false);
        $this->requireColons = (bool) ($emoji['require_colons'] ?? true);
        
        if(isset($emoji['roles'])) {
            $this->roles->clear();
            
            foreach($emoji['roles'] as $role) {
                $this->roles->set($role['id'], $this->guild->roles->get($role['id']));
            }
        }
        
        $this->client->emojis->set($this->id ?? $this->name, $this);
    }
}
