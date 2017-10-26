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
}
