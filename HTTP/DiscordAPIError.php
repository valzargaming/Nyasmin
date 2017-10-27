<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP;

/**
 * Represents an error from the Discord API.
 */
class DiscordAPIError extends \Exception {
    /**
     * The path of the request relative to the HTTP endpoint.
     * @var string
     */
    public $path;
    
    /**
     * Error code returned by Discord.
     * @var int
     */
    public $code;
    
    /**
     * @param string $path
     * @param array  $error
     */
    function __construct($path, array $error) {
        $this->path = $path;
        $this->code = (int) ($error['code'] ?? 0);
        
        $flattened = \implode('\n', self::flattenErrors(($error['errors'] ?? $error)));
        $this->message = (!empty($error['message']) && !empty($flattened) ? $error['message'].PHP_EOL.$flattened : ($error['message'] ?? $flattened));
    }
    
    /**
     * Flattens an errors object returned from the API into an array.
     * @param array   $obj  Discord error object
     * @param string  $key  Used internally to determine key names of nested fields
     * @return string[]
     * @access private
     */
    static function flattenErrors($obj, $key = '') {
        $messages = array();
        
        foreach($obj as $k => $val) {
            if($k === 'message') {
                continue;
            }
            
            $newKey = $k;
            if($key) {
                if(\is_numeric($k)) {
                    $newKey = $key.'.'.$k;
                } else {
                    $newKey = $key.'['.$k.']';
                }
            }
            
            if(isset($val['errors'])) {
                $messages[] = $newKey.': '.\implode(' ', \array_map(function ($element) {
                    return $element['message'];
                }, $val['errors']));
            } else if(isset($val['code']) || isset($val['message'])) {
                $messages[] = \trim(($val['code'] ?? '').': '.($val['message'] ?? ''));
            } else if(\is_array($val)) {
                $messages = \array_merge($messages, self::flattenErrors($val, $newKey));
            } else {
                $messages[] = $val;
            }
        }

        return $messages;
    }
}
