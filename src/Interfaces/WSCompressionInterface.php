<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * Interface for WS compressions. This is used internally.
 */
interface WSCompressionInterface {
    /**
     * Returns compression name (for gateway query string).
     * @return string
     */
    function getName(): string;
    
    /**
     * Returns a boolean for the OP code 2 IDENTIFY packet 'compress' parameter. The parameter is for payload compression.
     * @return bool
     */
    function isPayloadCompression(): bool;
    
    /**
     * Initializes the context.
     * @return void
     * @throws \Exception
     */
    function init(): void;
    
    /**
     * Destroys the context.
     * @return void
     */
    function destroy(): void;
    
    /**
     * Checks if the system supports it.
     * @return void
     * @throws \Exception
     */
    static function supported(): void;
    
    /**
     * Decompresses data.
     * @param string  $data
     * @return string
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    function decompress(string $data): string;
}
