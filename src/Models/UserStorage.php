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
 * User Storage to store and cache users, which utlizies Collection.
 */
class UserStorage extends Storage {
    /**
     * Resolves given data to an user.
     * @param \CharlotteDunois\Yasmin\Models\User|\CharlotteDunois\Yasmin\Models\GuildMember|string  $user  string = user ID
     * @return \CharlotteDunois\Yasmin\Models\User
     * @throws \InvalidArgumentException
     */
    function resolve($user) {
        if($user instanceof \CharlotteDunois\Yasmin\Models\User) {
            return $user;
        }
        
        if($user instanceof \CharlotteDunois\Yasmin\Models\GuildMember) {
            return $user->user;
        }
        
        if(\is_int($user)) {
            $user = (string) $user;
        }
        
        if(\is_string($user) && $this->has($user)) {
            return $this->get($user);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown user');
    }
    
    /**
     * @internal
     */
    function patch(array $user) {
        if($this->has($user['id'])) {
            return $this->get($user['id']);
        }
        
        if(count($user) === 1) {
            return null;
        }
        
        return $this->factory($user);
    }
    
    /**
     * @inheritDoc
     */
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->users) {
            $this->client->users->set($key, $value);
        }
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    function delete($key) {
        parent::delete($key);
        if($this !== $this->client->users) {
            $this->client->users->delete($key);
        }
        
        return $this;
    }
    
    /**
     * @internal
     */
    function factory(array $data) {
        if($this->has($data['id'])) {
            $user = $this->get($data['id']);
            $user->_patch($data);
            return $user;
        }
        
        $user = new \CharlotteDunois\Yasmin\Models\User($this->client, $data);
        $this->set($user->id, $user);
        
        return $user;
    }
}
