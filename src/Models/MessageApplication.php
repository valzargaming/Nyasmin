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
 * Represents a message application.
 *
 * @property string  $id           The ID of the application.
 * @property string  $name         The name of the application.
 * @property string  $icon         The hash of the application icon.
 * @property string  $coverImage   The hash of the application cover image.
 * @property string  $description  The description of the application.
 */
class MessageApplication extends ClientBase {
    protected $id;
    protected $name;
    protected $icon;
    protected $coverImage;
    protected $description;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $application) {
        parent::__construct($client);
        
        $this->id = $application['id'];
        $this->name = $application['name'];
        $this->icon = $application['icon'];
        $this->coverImage = $application['cover_image'];
        $this->description = $application['description'];
        
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
        
        return parent::__get($name);
    }
    
    /**
     * Returns the URL of the cover image.
     * @param int|null  $size  Any powers of 2.
     * @return string|null
     */
    function getCoverImageURL(int $size = null) {
        if($this->coverImage !== null) {
            return \CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['url'].\CharlotteDunois\Yasmin\HTTP\APIEndpoints::format(\CharlotteDunois\Yasmin\HTTP\APIEndpoints::CDN['appassets'], $this->id, $this->coverImage).(!empty($size) ? '?size='.$size : '');
        }
        
        return null;
    }
}
