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
 * Represents a voice region.
 *
 * @property string  $id              The ID of the region.
 * @property string  $name            The name of the region.
 * @property bool    $vip             Whether this is a VIP voice region.
 * @property bool    $optimal         Whether this is an optimal voice region for the client user.
 * @property bool    $deprecated      Whether this voice region is deprecated and therefore should be avoided.
 * @property bool    $custom          Whether the region is custom.
 */
class VoiceRegion extends ClientBase {
    protected $id;
    protected $name;
    protected $sampleHostname;
    protected $vip;
    protected $optimal;
    protected $deprecated;
    protected $custom;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $region) {
        parent::__construct($client);
        
        $this->id = $region['id'];
        $this->name = $region['name'];
        $this->vip = (bool) $region['vip'];
        $this->optimal = (bool) $region['optimal'];
        $this->deprecated = (bool) $region['deprecated'];
        $this->custom = (bool) $region['custom'];
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
}
