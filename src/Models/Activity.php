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
 * Something someone does.
 *
 * @property string                                                  $name           The name of the activity.
 * @property int                                                     $type           The type.
 * @property string|null                                             $url            The stream url, if streaming.
 *
 * @property string|null                                             $applicationID  The application ID associated with the activity, or null.
 * @property \CharlotteDunois\Yasmin\Models\RichPresenceAssets|null  $assets         Assets for rich presence, or null.
 * @property string|null                                             $details        Details about the activity, or null.
 * @property array|null                                              $party          Party of the activity, an array of ('id', 'size' => [ size, max ]), or null.
 * @property string|null                                             $state          State of the activity, or null.
 * @property array|null                                              $timestamps     Timestamps for the activity, an array of ('start' => \DateTime|null, 'end' => \DateTime|null), or null.
 *
 * @property bool                                                    $streaming      Whether or not the activity is being streamed.
 */
class Activity extends ClientBase {
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
        $this->party = $activity['party'] ?? null;
        $this->state = $activity['state'] ?? null;
        
        $this->assets = (!empty($activity['assets']) ? (new \CharlotteDunois\Yasmin\Models\RichPresenceAssets($this->client, $this, $activity['assets'])) : null);
        $this->timestamps = (!empty($activity['timestamps']) ? array(
            'start' => (!empty($activity['timestamps']['start']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime((int) $activity['timestamps']['start']) : null),
            'end' => (!empty($activity['timestamps']['end']) ? \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime((int) $activity['timestamps']['end']) : null)
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
