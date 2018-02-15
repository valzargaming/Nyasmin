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
 * Something someone does.
 *
 * @property string                                                  $name           The name of the activity.
 * @property int                                                     $type           The type.
 * @property string|null                                             $url            The stream url, if streaming.
 *
 * @property string|null                                             $applicationID  The application ID associated with the activity, or null.
 * @property \CharlotteDunois\Yasmin\Models\RichPresenceAssets|null  $assets         Assets for rich presence, or null.
 * @property string|null                                             $details        Details about the activity, or null.
 * @property array|null                                              $party          Party of the activity, an array in the format <code>[ 'id' => string, 'size' => [ size (int), max (int|null) ]|null ]</code>, or null.
 * @property string|null                                             $state          State of the activity, or null.
 * @property array|null                                              $timestamps     Timestamps for the activity, an array in the format <code>[ 'start' => \DateTime|null, 'end' => \DateTime|null ]</code>, or null.
 * @property int|null                                                $flags          The activity flags (as bitfield), like if an activity is a spectate activity.
 * @property string|null                                             $sessionID      The ID that links to the activity session.
 * @property string|null                                             $syncID         The sync ID. For spotify, this is the spotify track ID.
 *
 * @property bool                                                    $streaming      Whether or not the activity is being streamed.
 */
class Activity extends ClientBase {
    /**
     * The Activity flags.
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
    
    /**
     * The Activity types.
     * @var array
     * @source
     */
    const TYPES = array(
        0 => 'playing',
        1 => 'streaming',
        2 => 'listening',
        3 => 'watching'
    );
    
    protected $name;
    protected $type;
    protected $url;
    
    protected $applicationID;
    protected $assets;
    protected $details;
    protected $party;
    protected $state;
    protected $timestamps;
    
    protected $flags;
    protected $sessionID;
    protected $syncID;
    
    /**
     * The manual creation of such a class is discouraged. There may be an easy and safe way to create such a class in the future.
     * @param \CharlotteDunois\Yasmin\Client  $client      The client this instance is for.
     * @param array                           $activity    An array containing name, type (as int) and url (nullable).
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $activity) {
        parent::__construct($client);
        
        $this->name = $activity['name'];
        $this->type = $activity['type'];
        $this->url = (!empty($activity['url']) ? $activity['url'] : null);
        
        $this->applicationID = $activity['application_id'] ?? null;
        $this->details = $activity['details'] ?? null;
        $this->party = (!empty($activity['party']) ?
            array(
                ((string) ($activity['party']['id'] ?? '')),
                (!empty($activity['party']['size']) ?
                    array(
                        ((int) $activity['party']['size'][0]),
                        (isset($activity['party']['size'][1]) ? ((int) $activity['party']['size'][1]) : null)
                    ) : null)
            ) : null);
        $this->state = $activity['state'] ?? null;
        
        $this->assets = (!empty($activity['assets']) ? (new \CharlotteDunois\Yasmin\Models\RichPresenceAssets($this->client, $this, $activity['assets'])) : null);
        $this->timestamps = (!empty($activity['timestamps']) ? array(
            'start' => (!empty($activity['timestamps']['start']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime((int) $activity['timestamps']['start']) : null),
            'end' => (!empty($activity['timestamps']['end']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime((int) $activity['timestamps']['end']) : null)
        ) : null);
        
        $this->flags = $activity['flags'] ?? null;
        $this->sessionID = $activity['session_id'] ?? null;
        $this->syncID = $activity['sync_id'] ?? null;
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
            case 'streaming':
                return (bool) ($this->type === 1);
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Whether this activity is a rich presence.
     * @return bool
     */
    function isRichPresence() {
        return ($this->applicationID !== null || $this->party !== null || $this->sessionID !== null || $this->syncID !== null);
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
