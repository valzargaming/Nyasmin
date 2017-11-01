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
     * @internal
     */
    protected $clientPresence;
    
    /**
     * WS Presence Update ratelimit 5/60s.
     * @var int
     * @internal
     */
    protected $firstPresence;
    
    /**
     * @var int
     * @internal
     */
    protected $firstPresenceCount = 0;
    
    /**
     * @param \CharlotteDunois\Yasmin\Client $client
     * @param array                          $user
     * @internal
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
     * @internal
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
     *  Any field in the first dimension is optional and will be automatically filled with the last known value. Throws because fuck you and your spamming attitude.
     *
     * @param array $presence
     * @return \React\Promise\Promise<void>
     * @throws \BadMethodCallException
     */
    function setPresence(array $presence) {
        if(empty($presence)) {
            return \React\Promise\reject(new \InvalidArgumentException('Presence argument can not be empty'));
        }
        
        if($this->firstPresence && $this->firstPresence > (\time() - 60)) {
            if($this->firstPresenceCount >= 5) {
                throw new \BadMethodCallException('Stop spamming setPresence you idiot');
            }
            
            $this->firstPresenceCount++;
        } else {
            $this->firstPresence = \time();
            $this->firstPresence = 1;
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
    
    /**
     * Creates a new Group DM with the owner of the access tokens. The structure of the array is as following:
     *
     *  array(
     *      'accessToken' => \CharlotteDunois\Yasmin\Models\User|string (user ID)
     *  )
     *
     * The nicks array is an associative array of userID => nick. The nick defaults to the username.
     *
     * @param array  $userWithAccessTokens
     * @param array  $nicks
     * @return \React\Promise\Promise<\CharlotteDunois\Yasmin\Models\GroupDMChannel>
     */
    function createGroupDM(array $userWithAccessTokens, array $nicks = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($userWithAccessTokens) {
            $tokens = array();
            $users = array();
            
            foreach($userWithAccessTokens as $token => $user) {
                $user = $this->client->users->resolve($user);
                
                $tokens[] = $token;
                $users[$user->id] = (!empty($nicks[$user->id]) ? $nicks[$user->id] : $user->username);
            }
            
            $this->client->apimanager()->endpoints->user->createGroupDM($tokens, $users)->then(function ($data) use ($resolve) {
                $channel = $this->client->channels->factory($data);
                $resolve($channel);
            }, $reject);
        }));
    }
}
