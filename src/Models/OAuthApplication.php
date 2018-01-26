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
 * Represents an OAuth Application.
 *
 * @property string                                               $id                   The application ID.
 * @property string                                               $name                 The name of the application.
 * @property string|null                                          $icon                 The hash of the application hash, or null.
 * @property string|null                                          $description          The application's description, or null.
 * @property string[]|null                                        $rpcOrigins           An array of RPC origin url strings, if RPC is enabled, or null.
 * @property bool                                                 $botPublic            Whether the bot is public.
 * @property bool                                                 $botRequireCodeGrant  Whether the bot requires a code grant (full OAuth flow).
 * @property \CharlotteDunois\Yasmin\Models\User|null             $owner                The User instance of the owner, or null.
 */
class OAuthApplication extends ClientBase {
    protected $id;
    protected $name;
    protected $icon;
    protected $description;
    protected $rpcOrigins;
    protected $botPublic;
    protected $botRequireCodeGrant;
    protected $owner;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $application) {
        parent::__construct($client);
        
        $this->id = $application['id'];
        $this->name = $application['name'];
        $this->icon = $application['icon'] ?? null;
        $this->description = $application['description'] ?? null;
        $this->rpcOrigins = $application['rpc_origins'] ?? null;
        $this->botPublic = (bool) $application['bot_public'];
        $this->botRequireCodeGrant = (bool) $application['bot_require_code_grant'];
        $this->owner = (!empty($application['owner']) ? $this->client->users->patch($application['owner']) : null);
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
     * Returns the application's icon URL, or null.
     * @param string    $format  One of png, jpg or webp.
     * @param int|null  $size    One of 128, 256, 512, 1024 or 2048.
     * @return string|null
     */
    function getIconURL(string $format = 'png', ?int $size = null) {
        if($this->icon !== null) {
            return \CharlotteDunois\Yasmin\Constants::CDN['url'].\CharlotteDunois\Yasmin\Constants::format(\CharlotteDunois\Yasmin\Constants::CDN['appicons'], $this->id, $this->icon, $format).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
    
    /**
     * Automatically converts the OAuth Application instance to the application name.
     */
    function __toString() {
        return $this->name;
    }
}
