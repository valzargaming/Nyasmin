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
 * @property int                                           $type      The message activity type (flags).
 * @property \CharlotteDunois\Yasmin\Models\User           $user      The user this message activity is for.
 *
 * @property \CharlotteDunois\Yasmin\Models\Activity|null  $activity  The activity this message activity links to.
 */
class MessageActivity extends ClientBase {
    /**
     * The Message Activity flags.
     * @var array
     * @source
     */
    const FLAGS = array(
        'INSTANCE' => 1,
        'JOIN' => 2,
        'SPECTATE' => 4,
        'JOIN_REQUEST' => 8,
        'SYNC' => 16,
        'PLAY' => 32
    );
    
    protected $type;
    protected $user;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $activity) {
        parent::__construct($client);
        
        $this->type = $activity['type'];
        
        $name = \explode(':', $activity['party_id']);
        $this->user = $this->client->users->get(($name[1] ?? $name[0]));
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
