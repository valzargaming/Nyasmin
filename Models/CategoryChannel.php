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
 * Represents a guild's category channel.
 */
class CategoryChannel extends TextBasedChannel {
    use \CharlotteDunois\Yasmin\Traits\GuildChannelTrait;
    
    protected $guild;
    
    protected $name;
    protected $parentID;
    protected $position;
    protected $permissionsOverwrites;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        parent::__construct($client, $channel);
        $this->guild = $guild;
        
        $this->permissionsOverwrites = new \CharlotteDunois\Yasmin\Models\Collection();
        
        $this->name = $channel['name'] ?? $this->name ?? '';
        $this->parentID = $channel['parent_id'] ?? $this->parentID ?? null;
        $this->position = $channel['position'] ?? $this->position ?? 0;
        
        if(!empty($channel['permissions_overwrites'])) {
            foreach($channel['permissions_overwrites'] as $permission) {
                $this->permissionsOverwrites->set($permission['id'], new \CharlotteDunois\Yasmin\Models\PermissionOverwrite($client, $this, $permission));
            }
        }
    }
    
    /**
     * @inheritdoc
     * @property-read \CharlotteDunois\Yasmin\Models\Collection  $children  Returns all channels which are childrens of this category.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            case 'children':
                return $this->guilds->channels->filter(function ($channel) {
                    return $channel->parentID === $this->id;
                });
            break;
        }
        
        return null;
    }
}
