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
 * Represents an user's voice state.
 */
class VoiceState extends ClientBase { //TODO: Implementation
    protected $channel;
    
    protected $sessionID;
    protected $user;
    protected $deaf;
    protected $mute;
    protected $selfDeaf;
    protected $selfMute;
    protected $suppress;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel = null, array $voice) {
        parent::__construct($client);
        $this->channel = $channel;
        
        $this->sessionID = $voice['session_id'];
        $this->user = $client->users->get($voice['user_id']);
        $this->deaf = (bool) $voice['deaf'];
        $this->mute = (bool) $voice['mute'];
        $this->selfDeaf = (bool) $voice['self_deaf'];
        $this->selfMute = (bool) $voice['self_mute'];
        $this->suppress = (bool) $voice['suppress'];
    }
    
    /**
     * @property-read string                                                    $sessionID   The voice session ID.
     * @property-read \CharlotteDunois\Yasmin\Models\User                   $user        The user this voice state belongs to.
     * @property-read \CharlotteDunois\Yasmin\Interfaces\ChannelInterface|null  $channel     The channel this voice state is for.
     * @property-read bool                                                      $deaf        Whether the user is server deafened.
     * @property-read bool                                                      $mute        Whether the user is server muted.
     * @property-read bool                                                      $selfDeaf    Whether the user is locally deafened.
     * @property-read bool                                                      $selfMute    Whether the user is locally muted.
     * @property-read bool                                                      $suppress    Do you suppress the user or what?
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return null;
    }
    
    /**
     * @access private
     */
    function _updateChannel(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel = null) {
        $this->channel = $channel;
    }
}
