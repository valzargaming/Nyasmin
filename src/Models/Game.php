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
 * Something someone plays.
 *
 * @property string       $name        The name of the game.
 * @property int          $type        The type.
 * @property string|null  $url         The stream url, if streaming.
 *
 * @property string|null                                     $applicationID  The application ID associated with the game, or null.
 * @property \CharlotteDunois\Yasmin\Models\GameAssets|null  $assets         Assets for rich presence, or null.
 * @property string|null                                     $details        Details about the activity, or null.
 * @property array|null                                      $party          Party of the activity, an array of ('id', 'size' => [ size, max ]), or null.
 * @property string|null                                     $state          State of the activity, or null.
 * @property array|null                                      $timestamps     Timestamps for the activity, an array of ('start' => \DateTime|null, 'end' => \DateTime|null), or null.
 *
 * @property bool         $streaming   Whether or not the game is being streamed.
 */
class Game extends ClientBase {
    protected $name;
    protected $type;
    protected $url;
    
    protected $applicationID;
    protected $assets;
    protected $details;
    protected $party;
    protected $state;
    protected $timestamps;
    
    /**
     * The manual creation of such an object is discouraged. There may be an easy and safe way to create such an object in the future.
     * @param \CharlotteDunois\Yasmin\Client  $client  The client this object is for.
     * @param array                           $game    An array containing name, type (as int) and url (nullable).
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $game) {
        parent::__construct($client);
        
        $this->name = $game['name'];
        $this->type = $game['type'];
        $this->url = (!empty($game['url']) ? $game['url'] : null);
        
        $this->applicationID = $game['application_id'] ?? null;
        $this->details = $game['details'] ?? null;
        $this->party = $game['party'] ?? null;
        $this->state = $game['state'] ?? null;
        
        $this->assets = (!empty($game['assets']) ? (new \CharlotteDunois\Yasmin\Models\GameAssets($this->client, $this, $game['assets'])) : null);
        $this->timestamps = (!empty($game['timestamps']) ? array(
            'start' => (!empty($game['timestamps']['start']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($game['timestamps']['start']) : null),
            'end' => (!empty($game['timestamps']['end']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($game['timestamps']['end']) : null)
        ) : null);
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
        
        switch($name) {
            case 'streaming':
                return (bool) ($this->type === 1);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * @internal
     */
    function jsonSerialize() {
        return array(
            'name' => $this->name,
            'type' => $this->type,
            'url' => $this->url
        );
    }
}
