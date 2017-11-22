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
 * Represents a presence.
 *
 * @property \CharlotteDunois\Yasmin\Models\User           $user      The user this presence belongs to.
 * @property \CharlotteDunois\Yasmin\Models\Activity|null  $activity  The activity the user is doing.
 * @property string                                        $status    What do you expect this to be?
 */
class Presence extends ClientBase {
    /**
     * The user this presence belongs to.
     * @var \CharlotteDunois\Yasmin\Models\User|null
     */
    protected $user;
    
    /**
     * @var \CharlotteDunois\Yasmin\Models\Activity
     */
    protected $activity;
    
    /**
     * @var string
     */
    protected $status;
    
    /**
     * The manual creation of such an object is discouraged. There may be an easy and safe way to create such an object in the future.
     * @param \CharlotteDunois\Yasmin\Client  $client  The client this object is for.
     * @param array                           $activity    An array containing user (as array, with an element id), activity (as array) and status.
     *
     * @throws \Exception
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $presence) {
        parent::__construct($client);
        $this->user = $this->client->users->get($presence['user']['id']);
        
        $this->_patch($presence);
    }
    
    /**
     * @inheritDoc
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
    
    /**
     * @internal
     */
     function jsonSerialize() {
         return array(
             'status' => $this->status,
             'game' => $this->activity
         );
     }
     
     /**
      * @internal
      */
     function _patch(array $presence) {
         $this->activity = (!empty($presence['game']) ? (new \CharlotteDunois\Yasmin\Models\Activity($this->client, $presence['game'])) : null);
         $this->status = $presence['status'];
     }
}
