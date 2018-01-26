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
 * Represents the Client User.
 */
class ClientUser extends User {
    /**
     * @var array
     * @internal
     */
    protected $clientPresence;
    
    /**
     * Holds the latest set presence while waiting for the ratelimit to lift.
     * WS Presence Update ratelimit 5/60s.
     * @var array
     * @internal
     */
    protected $firstPresence = array(
        'presence' => null,
        'promise' => null,
        'count' => 0,
        'time' => 0
    );
    
    /**
     * @param \CharlotteDunois\Yasmin\Client $client
     * @param array                          $user
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, $user) {
        parent::__construct($client, $user);
        
        $presence = $this->client->getOption('ws.presence', array());
        $this->clientPresence = array(
            'afk' => (isset($presence['afk']) ? ((bool) $presence['afk']) : false),
            'since' => (isset($presence['since']) ? $presence['since'] : null),
            'status' => (!empty($presence['status']) ? $presence['status'] : 'online'),
            'game' => (!empty($presence['game']) ? $presence['game'] : null)
        );
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
        
        return parent::__get($name);
    }
    
    /**
     * @internal
     */
    function __debugInfo() {
        $vars = parent::__debugInfo();
        unset($vars['clientPresence'], $vars['firstPresence'], $vars['firstPresencePromise'], $vars['firstPresenceCount'], $vars['firstPresenceTime']);
        return $vars;
    }
    
    /**
     * Set your avatar. Resolves with $this.
     * @param string|null  $avatar  An URL or the filepath or the data. Null resets your avatar.
     * @return \React\Promise\Promise
     */
    function setAvatar(?string $avatar) {
        if($avatar === null) {
            return $this->client->apimanager()->endpoints->user->modifyCurrentUser(array('avatar' => null))->then(function () {
                return $this;
            });
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($avatar) {
            \CharlotteDunois\Yasmin\Utils\DataHelpers::resolveFileResolvable($avatar)->then(function ($data) use ($resolve, $reject) {
                $image = \CharlotteDunois\Yasmin\Utils\DataHelpers::makeBase64URI($data);
                
                $this->client->apimanager()->endpoints->user->modifyCurrentUser(array('avatar' => $image))->then(function () use ($resolve) {
                    $resolve($this);
                }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Set your username. Resolves with $this.
     * @param string $username
     * @return \React\Promise\Promise
     */
    function setUsername(string $username) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($username) {
            $this->client->apimanager()->endpoints->user->modifyCurrentUser(array('username' => $username))->then(function () use ($resolve) {
                $resolve($this);
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Set your status. Resolves with $this.
     * @param string $status  Valid values are: online, idle, dnd and offline.
     * @return \React\Promise\Promise
     */
    function setStatus(string $status) {
        $presence = array(
            'status' => $status
        );
        
        return $this->setPresence($presence);
    }
    
    /**
     * Set your activity. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\Activity|string|null  $name  The activity name.
     * @param int                                                  $type  Optional if first argument is an Activity. The type of your activity. Should be listening (2) or watching (3). For playing/streaming use ClientUser::setGame.
     * @return \React\Promise\Promise
     */
    function setActivity($name, int $type = 0) {
        if($name === null) {
            return $this->setPresence(array(
                'game' => null
            ));
        } elseif($name instanceof \CharlotteDunois\Yasmin\Models\Activity) {
            return $this->setPresence(array(
                'game' => $name->jsonSerialize()
            ));
        }
        
        $presence = array(
            'game' => array(
                'name' => $name,
                'type' => $type,
                'url' => null
            )
        );
        
        return $this->setPresence($presence);
    }
    
    /**
     * Set your playing game. Resolves with $this.
     * @param string|null  $name  The game name.
     * @param string       $url   If you're streaming, this is the url to the stream.
     * @return \React\Promise\Promise
     */
    function setGame(?string $name, string $url = '') {
        if($name === null) {
            return $this->setPresence(array(
                'game' => null
            ));
        }
        
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
     * Set your presence. Ratelimit is 5/60s, which gets handled by this method and after the ratelimit passed, it will set the last set presence as presence, skipping all previous set presences. Resolves with $this.
     *
     *  $presence = array(
     *   'afk' => bool,
     *   'since' => integer|null,
     *   'status' => string,
     *   'game' => array(
     *          'name' => string,
     *          'type' => int,
     *          'url' => string|null
     *      )|null
     *  )
     *
     *  Any field in the first dimension is optional and will be automatically filled with the last known value.
     *
     * @param array $presence
     * @return \React\Promise\Promise
     */
    function setPresence(array $presence) {
        if(empty($presence)) {
            return \React\Promise\reject(new \InvalidArgumentException('Presence argument can not be empty'));
        }
        
        if($this->firstPresence['time'] > (\time() - 60)) {
            $this->firstPresence['count']++;
            
            if($this->firstPresence['count'] > 5) {
                $this->firstPresence['presence'] = $presence;
                
                if($this->firstPresence['promise'] === null) {
                    $this->firstPresence['promise'] = new \React\Promise\Promise(function (callable $resolve, callable $reject) {
                        $this->client->addTimer((60 - (\time() - $this->firstPresence['time'])), function () use ($resolve, $reject) {
                            $this->firstPresence['promise'] = null;
                            $this->setPresence($this->firstPresence['presence'])->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
                        });
                    });
                }
                
                return $this->firstPresence['promise'];
            }
        } else {
            $this->firstPresence['count'] = 1;
            $this->firstPresence['time'] = \time();
        }
        
        $packet = array(
            'op' => \CharlotteDunois\Yasmin\Constants::OPCODES['STATUS_UPDATE'],
            'd' => array(
                'afk' => (\array_key_exists('afk', $presence) ? ((bool) $presence['afk']) : $this->clientPresence['afk']),
                'since' => (\array_key_exists('since', $presence) ? $presence['since'] : $this->clientPresence['since']),
                'status' => (\array_key_exists('status', $presence) ? $presence['status'] : $this->clientPresence['status']),
                'game' => (\array_key_exists('game', $presence) ? $presence['game'] : $this->clientPresence['game'])
            )
        );
        
        $this->clientPresence = $packet['d'];
        
        $presence = $this->presence;
        if($presence) {
            $presence->_patch($this->clientPresence);
        }
        
        return $this->client->wsmanager()->send($packet)->then(function () {
            return $this;
        });
    }
    
    /**
     * Creates a new Group DM with the owner of the access tokens. Resolves with an instance of GroupDMChannel. The structure of the array is as following:
     *
     * <pre>
     * array(
     *   'accessToken' => \CharlotteDunois\Yasmin\Models\User|string (user ID)
     * )
     * </pre>
     *
     * The nicks array is an associative array of userID => nick. The nick defaults to the username.
     *
     * @param array  $userWithAccessTokens
     * @param array  $nicks
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\GroupDMChannel
     */
    function createGroupDM(array $userWithAccessTokens, array $nicks = array()) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($nicks, $userWithAccessTokens) {
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
            }, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
        }));
    }
    
    /**
     * Making these methods throw if someone tries to use them. They also get hidden due to the Sami Renderer removing them.
    */
    
    /**
     * @throws \Error
     * @internal
     */
    function createDM() {
        throw new \Error('Can not use this method in ClientUser');
    }
    
    /**
     * @throws \Error
     * @internal
     */
    function deleteDM() {
        throw new \Error('Can not use this method in ClientUser');
    }
}
