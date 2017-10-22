<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Webhook extends Structure { //TODO: Implementation
    
    protected $id;
    protected $username;
    protected $avatar;
    
    protected $createdTimestamp;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $user) {
        parent::__construct($client);
        
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->avatar = $user['avatar'];
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @property-read string                                               $id                 The user ID.
     * @property-read string                                               $username           The username.
     * @property-read string                                               $avatar             The hash of the user's avatar.
     * @property-read int                                                  $createdTimestamp   The timestamp of when this user was created.
     *
     * @property-read \DateTime                                            $createdAt          An DateTime object of the createdTimestamp.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return (new \DateTime('@'.$this->createdTimestamp));
            break;
        }
        
        return null;
    }
    
    /**
     * Automatically converts the User object to a mention.
     */
    function __toString() {
        return '<@'.$this->id.'>';
    }
    
    private function getAvatarExtension() {
        return (strpos($this->avatar, 'a_') === 0 ? 'gif' : 'webp');
    }
}
