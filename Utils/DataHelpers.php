<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Utils;

/**
 * Data Helper methods.
 */
class DataHelpers {
    /**
     * Resolves a color to an int.
     * @param array|int|string  $color
     * @return int
     * @throws \InvalidArgumentException
     */
    static function resolveColor($color) {
        if(\is_int($color)) {
            return $color;
        }
        
        $input = (string) $color;
        
        if(!\is_array($color)) {
            $color = \str_split(\str_replace('#', '', (string) $color), 2);
        }
        
        if(\count($color) < 1) {
            throw new \InvalidArgumentException('Color "'.$input.'" is not resolvable');
        }
        
        return ((\hexdec($color[0]) << 16) + (\hexdec($color[1] ?? '0') << 8) + \hexdec($color[2] ?? '0'));
    }
    
    /**
     * Makes a DateTime object from an UNIX timestamp and applies the default timezone.
     * @param int $timestamp
     * @return \DateTime
     */
    static function makeDateTime(int $timestamp) {
        $zone = new \DateTimeZone(\date_default_timezone_get());
        return (new \DateTime('@'.$timestamp))->setTimezone($zone);
    }
    
    /**
     * Turns input into a base64-encoded data URI.
     * @param string  $data
     * @return string
     * @throws \BadMethodCallException
     */
    static function makeBase64URI(string $data) {
        $img = \getimagesizefromstring($data);
        if(!$img) {
            throw new \BadMethodCallException('Bad input data');
        }
        
        return 'data:'.$img['mime'].';base64,'.\base64_encode($data);
    }
}
