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
 * Represents a message activity.
 *
 * @property int                                           $type      The message activity type.
 * @property \CharlotteDunois\Yasmin\Models\User           $user      The user this message activity is for.
 *
 * @property \CharlotteDunois\Yasmin\Models\Activity|null  $activity  The activity this message activity points to.
 *
 * @see \CharlotteDunois\Yasmin\Constants::MESSAGE_ACTIVITY_TYPES
 */
class MessageActivity extends ClientBase {
    protected $type;
    protected $user;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $activity) {
        parent::__construct($client);
        
        $this->type = $activity['type'];
        
        if(!empty($activity['party_id'])) {
            $name = \explode(':', $activity['party_id']);
            $this->user = $this->client->users->get(($name[1] ?? $name[0]));
        }
    }
    
    /**
     * @inheritDoc
     *
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'activity':
                if($this->user) {
                    $presence = $this->user->presence;
                    if($presence !== null && $presence->activity !== null) {
                        return $presence->activity;
                    }
                }
                
                return null;
            break;
        }
        
        return parent::__get($name);
    }
}
