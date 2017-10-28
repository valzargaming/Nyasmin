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
 * Represents the Client User.
 */
class ClientUser extends User { //TODO: Implementation
    /**
     * @var array
     * @access private
     */
    protected $clientPresence;
    
    /**
     * @param \CharlotteDunois\Yasmin\Client $client
     * @param array                          $user
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, $user) {
        parent::__construct($client, $user);
        
        $presence = $this->client->getOption('ws.presence', array());
        $this->clientPresence = array(
            'afk' => (isset($presence['afk']) ? (bool) $presence['afk'] : false),
            'since' => (!empty($presence['since']) ? (int) $presence['since'] : null),
            'status' => (!empty($presence['status']) ? $presence['status'] : 'online'),
            'game' => (!empty($presence['game']) ? $presence['game'] : null)
        );
    }
    
    /**
     * @inheritdoc
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
    
    /**
     * @access private
     */
    function __debugInfo() {
        $vars = parent::__debugInfo();
        unset($vars['clientPresence']);
        return $vars;
    }
    
    /**
     * Set your avatar.
     * @param string $avatar  An URL or the filepath or the data.
     * @return \React\Promise\Promise<void>
     */
    function setAvatar(string $avatar) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($avatar) {
            $file = @\realpath($avatar);
            if($file) {
                $promise = \React\Promise\resolve(\file_get_contents($file));
            } elseif(\filter_var($avatar, FILTER_VALIDATE_URL)) {
                $promise = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($avatar);
            } else {
                $promise = \React\Promise\resolve($avatar);
            }
            
            $promise->then(function ($data) use ($resolve, $reject) {
                $img = \getimagesizefromstring($data);
                $image = 'data:'.$img['mime'].';base64,'.\base64_encode($data);
                
                $this->client->apimanager()->endpoints->user->modifyCurrentUser(array('avatar' => $image))->then(function ($data) use ($resolve) {
                    $this->_patch($data);
                    $resolve();
                }, $reject);
            }, $reject);
        }));
    }
    
    /**
     * Set your username.
     * @param string $username
     * @return \React\Promise\Promise<void>
     */
    function setUsername(string $username) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($username) {
            $this->client->apimanager()->endpoints->user->modifyCurrentUser(array('username' => $username))->then(function () use ($resolve) {
                $this->_patch($data);
                $resolve();
            }, $reject);
        }));
    }
    
    /**
     * Set your status.
     * @param string $status  Valid values are: online, idle, dnd and offline.
     * @return \React\Promise\Promise<void>
     */
    function setStatus(string $status) {
        $presence = array(
            'status' => $status
        );
        
        return $this->setPresence($presence);
    }
    
    /**
     * Set your playing game.
     * @param string       $name  The game name.
     * @param string|void  $url   If you're streaming, this is the url to the stream.
     * @return \React\Promise\Promise<void>
     */
    function setGame(string $name, string $url = '') {
        $presence = array(
            'game' => array(
                'name' => $name,
                'type' => 0,
                'url' => null
            )
        );
        
        if(!empty($url)) {
            $presence['game']['type'] = 1;
            $presence['game']['url'] = $url;
        }
        
        return $this->setPresence($presence);
    }
    
    /**
     * Set your presence.
     *
     *  $presence = array(
     *      'afk' => bool,
     *      'since' => integer|null,
     *      'status' => string,
     *      'game' => array(
     *          'name' => string,
     *          'type' => int,
     *          'url' => string|null
     *      )|null
     *  )
     *
     *  Any field in the first dimension is optional and will be automatically filled with the last known value.
     *
     * @param array $presence
     * @return \React\Promise\Promise<void>
     */
    function setPresence(array $presence) {
        if(empty($presence)) {
            return \React\Promise\reject(new \InvalidArgumentException('Presence argument can not be empty'));
        }
        
        $packet = array(
            'op' => \CharlotteDunois\Yasmin\Constants::OPCODES['STATUS_UPDATE'],
            'd' => array(
                'afk' => (!empty($presence['afk']) ? $presence['afk'] : $this->clientPresence['afk']),
                'since' => (!empty($presence['since']) ? $presence['since'] : $this->clientPresence['since']),
                'status' => (!empty($presence['status']) ? $presence['status'] : $this->clientPresence['status']),
                'game' => (!empty($presence['game']) ? $presence['game'] : $this->clientPresence['game'])
            )
        );
        
        $this->clientPresence = $packet['d'];
        $presence = $this->presence;
        if($presence) {
            $presence->_patch($this->clientPresence);
        }
        
        return $this->client->wsmanager()->send($packet);
    }
}
