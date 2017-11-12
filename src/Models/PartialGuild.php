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
 *
 * @property string       $id                The guild ID.
 * @property string       $name              The guild name.
 * @property int          $createdTimestamp  The timestmap when this guild was created.
 * @property string|null  $icon              The guild icon.
 * @property string|null  $splash            The guild splash.
 *
 * @property \DateTime   $createdAt          The DateTime object of createdTimestamp.
 */
class PartialGuild extends ClientBase {
    protected $id;
    protected $name;
    protected $icon;
    protected $splash;
    
    protected $createdTimestamp;
    
    /**
     * @internal
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
            case 'createdAt':
                return \CharlotteDunois\Yasmin\Utils\DataHelpers::makeDateTime($this->createdTimestamp);
            break;
        }
        
        return parent::__get($name);
    }
}
