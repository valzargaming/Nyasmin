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
 * Represents a partial guild.
 *
 * @property string       $id                The guild ID.
 * @property string       $name              The guild name.
 * @property int          $createdTimestamp  The timestamp when this guild was created.
 * @property string|null  $icon              The guild icon, or null.
 * @property string|null  $splash            The guild splash, or null.
 *
 * @property \DateTime   $createdAt          The DateTime instance of createdTimestamp.
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
    
    /**
     * Returns the guild's icon URL, or null.
     * @param string    $format  One of png, jpg or webp.
     * @param int|null  $size    One of 128, 256, 512, 1024 or 2048.
     * @return string|null
     */
    function getIconURL(string $format = 'png', ?int $size = null) {
        if($this->icon !== null) {
            return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['icons'], $this->id, $this->icon, $format).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * Returns the guild's splash URL, or null.
     * @param string    $format  One of png, jpg or webp.
     * @param int|null  $size    One of 128, 256, 512, 1024 or 2048.
     * @return string|null
     */
    function getSplashURL(string $format = 'png', ?int $size = null) {
        if($this->splash !== null) {
            return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['splashes'], $this->id, $this->splash, $format).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
}
