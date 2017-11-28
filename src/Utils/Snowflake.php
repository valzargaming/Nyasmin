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
 * Represents a Snowflake.
 * @property float      $timestamp
 * @property int        $workerID
 * @property int        $processID
 * @property int        $increment
 * @property string     $binary
 * @property \DateTime  $date
 * @todo 64bit
 */
class Snowflake {
    /**
     * Time since UNIX epoch to Discord epoch.
     * @var int
     */
    const EPOCH = 1420070400;
    
    static private $incrementIndex = 0;
    
    protected $timestamp;
    protected $workerID;
    protected $processID;
    protected $increment;
    protected $binary;
    
    /**
     * @param string $snowflake
     */
    function __construct(string $snowflake) {
        $this->binary = \str_pad(\base_convert($snowflake, 10, 2), 64, 0, \STR_PAD_LEFT);
        
        $time = \base_convert(\substr($this->binary, 0, 42), 2, 10);
        
        $this->timestamp = (float) ((((int) \substr($time, 0, -3)) + self::EPOCH).'.'.\substr($time, -3));
        $this->workerID = (int) \base_convert(\substr($this->binary, 42, 5), 2, 10);
        $this->processID = (int) \base_convert(\substr($this->binary, 47, 5), 2, 10);
        $this->increment = (int) \base_convert(\substr($this->binary, 52, 12), 2, 10);
    }
    
    /**
     * @throws \Exception
     */
    function __get($name) {
        switch($name) {
            case 'timestamp':
            case 'workerID':
            case 'processID':
            case 'increment':
            case 'binary':
                return $this->$name;
            break;
            case 'date':
                return (new \DateTime('@'.((int) $this->timestamp)));
            break;
        }
        
        throw new \Exception('Undefined property: '.(self::class).'::$'.$name);
    }
    
    /**
     * Deconstruct a snowflake.
     * @param string $snowflake
     * @return Snowflake
     */
    static function deconstruct(string $snowflake) {
        return (new self($snowflake));
    }
    
    /**
     * Generates a new snowflake with worker ID hardcoded to 1 and process ID hardcoded to 0.
     * @return string
     */
    static function generate() {
        if(self::$incrementIndex >= 4095) {
            self::$incrementIndex = 0;
        }
        
        $mtime = \explode('.', (string) \microtime(true));
        $time = ((string) (((int) $mtime[0]) - self::EPOCH)).\substr($mtime[1], 0, 3);
        
        $binary = \str_pad(\base_convert($time, 10, 2), 42, 0, \STR_PAD_LEFT).'0000100000'.\str_pad(\base_convert((self::$incrementIndex++), 10, 2), 12, 0, \STR_PAD_LEFT);
        return \base_convert($binary);
    }
    
    /**
     * Is this a valid Snowflake or not? This does not determine if a given Snowflake exists in Discord.
     * @return bool
     */
    function isValid() {
        return ($this->timestamp < \time() && $this->workerID >= 0 && $this->processID >= 0 && $this->increment >= 0 && $this->increment <= 4095);
    }
}
