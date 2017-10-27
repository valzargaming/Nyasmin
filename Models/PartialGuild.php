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
 * Represents a partial guild.
 */
class PartialGuild extends Structure {
    protected $id;
    protected $name;
    protected $icon;
    protected $splash;
    
    protected $createdTimestamp;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $guild) {
        parent::__construct($client);
        
        $this->id = $guild['id'];
        $this->name = $guild['name'];
        $this->icon = $guild['icon'] ?? null;
        $this->splash = $guild['splash'] ?? null;
        
        $this->createdTimestamp = (int) \CharlotteDunois\Yasmin\Utils\Snowflake::deconstruct($this->id)->timestamp;
    }
    
    /**
     * @property-read string       $id                The guild ID.
     * @property-read string       $name              The guild name.
     * @property-read int          $createdTimestamp  The timestmap when this guild was created.
     * @property-read string|null  $icon              The guild icon.
     * @property-read string|null  $splash            The guild splash.
     *
     * @property-read \DateTime   $createdAt          The DateTime object of createdTimestamp.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'createdAt':
                return (new \DateTime('@'.$this->createdTimestamp));
            break;
        }
        
        return null;
    }
}
