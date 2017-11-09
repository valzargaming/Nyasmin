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
 * Represents a voice region.
 */
class VoiceRegion extends ClientBase {
    protected $id;
    protected $name;
    protected $sampleHostname;
    protected $vip;
    protected $optimal;
    protected $deprecated;
    protected $custom;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $region) {
        parent::__construct($client);
        
        $this->id = $region['id'];
        $this->name = $region['name'];
        $this->sampleHostname = $region['sample_hostname'];
        
        $this->vip = (bool) $region['vip'];
        $this->optimal = (bool) $region['optimal'];
        $this->deprecated = (bool) $region['deprecated'];
        $this->custom = (bool) $region['custom'];
    }
    
    /**
     * @inheritDoc
     *
     * @property-read string  $id              The ID of the region.
     * @property-read string  $name            The name of the region.
     * @property-read string  $sampleHostname  A sample hostname for what a connection may look like.
     * @property-read bool    $vip             Whether this is a VIP voice region.
     * @property-read bool    $optimal         Whether this is an optimal voice region for the client user.
     * @property-read bool    $deprecated      Whether this voice region is deprecated and therefore should be avoided.
     * @property-read bool    $custom          Whether the region is custom.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
}
