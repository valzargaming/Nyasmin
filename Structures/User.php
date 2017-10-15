<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class User extends Structure { //TODO
    protected $id;
    protected $username;
    protected $discriminator;
    protected $avatar;
    protected $email;
    protected $verified;
    protected $tag;
    
    function __construct($client, $user) {
        parent::__construct($client);
        
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->discriminator = $user['discriminator'];
        $this->avatar = $user['avatar'];
        $this->email = $user['email'];
        $this->verified = $user['verified'];
        
        $this->tag = $this->username.'#'.$this->discriminator;
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        return NULL;
    }
    
    function defaultAvatar() {
        return ($this->discriminator % 5);
    }
    
    function getDefaultAvatarURL($size = 256) {
        return \CharlotteDunois\Yasmin\Constants::$cdn['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::$cdn['defaultavatars'], ($this->discriminator % 5)).'?size='.$size;
    }
    
    function getAvatarURL($size = 256, $format = '') {
        if(!$this->avatar) {
            return NULL;
        }
        
        if(empty($format)) {
            $format = $this->getAvatarExtension();
        }
        
        return \CharlotteDunois\Yasmin\Constants::$cdn['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::$cdn['avatars'], $this->id, $this->avatar, $format).'?size='.$size;
    }
    
    function getDisplayAvatarURL($size = 256, $format = '') {
        return ($this->avatar ? $this->getAvatarURL($format) : $this->getDefaultAvatarURL());
    }
    
    private function getAvatarExtension() {
        return (strpos($this->avatar, 'a_') === 0 ? 'gif' : 'webp');
    }
}
