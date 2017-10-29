<<<<<<< current
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
 */
class Snowflake { //TODO: 64bit
    /**
     * @var int Time since UNIX epoch to Discord epoch.
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
        $this->binary = \str_pad(self::convertBase($snowflake, 10, 2), 64, 0, STR_PAD_LEFT);
        
        $time = self::convertBase(\substr($this->binary, 0, 42), 2, 10);
        
        $this->timestamp = (float) ((((int) \substr($time, 0, -3)) + self::EPOCH).'.'.\substr($time, -3));
        $this->workerID = (int) self::convertBase(\substr($this->binary, 42, 5), 2, 10);
        $this->processID = (int) self::convertBase(\substr($this->binary, 47, 5), 2, 10);
        $this->increment = (int) self::convertBase(\substr($this->binary, 52, 12), 2, 10);
    }
    
    /**
     * @property-read float      $timestamp
     * @property-read int        $workerID
     * @property-read int        $processID
     * @property-read int        $increment
     * @property-read string     $binary
     * @property-read \DateTime  $date
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
        
        $binary = \str_pad(self::convertBase($time, 10, 2), 42, 0, STR_PAD_LEFT).'0000100000'.\str_pad(self::convertBase((self::$incrementIndex++), 10, 2), 12, 0, STR_PAD_LEFT);
        return self::convertBase($binary, 2, 10);
    }
    
    /**
     * Is this a valid Snowflake or not? This does not determine if a given Snowflake exists in Discord.
     * @return bool
     */
    function isValid() {
        return ($this->timestamp < \time() && $this->workerID >= 0 && $this->processID >= 0 && $this->increment >= 0 && $this->increment <= 4095);
    }
    
    /**
     * Converts numbers from one base to another.
     * @param string $input
     * @param int    $fromBase
     * @param int    $toBase
     */
    static function convertBase($input, $fromBase, $toBase) {
        if($fromBase === $toBase) {
            return $input;
        }
        
        $fromBaseArr = self::getBaseArray($fromBase);
        $toBaseArr = self::getBaseArray($toBase);
        
        $numberLen = \strlen($input);
        $number = \str_split($input, 1);
        
        $retval = '';
        
        if($toBase === 10) {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++) {
                $retval = \bcadd($retval, \bcmul(\array_search($number[($i - 1)], $fromBaseArr), \bcpow($fromBase, ($numberLen - $i))));
            }
            
            return $retval;
        }
        
        if($fromBase != 10) {
            $base10 = self::convertBase($input, $fromBase, 10);
        } else {
            $base10 = $input;
        }
        
        if($base10 < $toBase) {
            return $toBaseArr[$base10];
        }
        
        while($base10 != 0) {
            $retval = $toBaseArr[\bcmod($base10, $toBase)].$retval;
            $base10 = \bcdiv($base10, $toBase, 0);
        }
        
        return $retval;
    }
    
    /**
     * Return the valid values for a given base.
     * @param string|int $base
     * @return array
     */
    static private function getBaseArray($base) {
        switch((int) $base) {
            case 2:
                return array(0, 1);
            break;
            case 8:
                return array(0, 1, 2, 3, 4, 5, 6, 7);
            break;
            default:
            case 10:
                return array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
            break;
            case 16:
                return array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F');
            break;
        }
    }
}
=======
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
 */
class Snowflake { //TODO: 64bit
    /**
     * @var int Time since UNIX epoch to Discord epoch.
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
        $this->binary = \str_pad(self::convertBase($snowflake, 10, 2), 64, 0, STR_PAD_LEFT);
        
        $time = self::convertBase(\substr($this->binary, 0, 42), 2, 10);
        
        $seconds = ((int) \substr($time, 0, -3)) + self::EPOCH;
        $milli = \substr($time, -3);
        
        $this->timestamp = (float) ($seconds.'.'.$milli);
        $this->workerID = (int) self::convertBase(\substr($this->binary, 42, 5), 2, 10);
        $this->processID = (int) self::convertBase(\substr($this->binary, 47, 5), 2, 10);
        $this->increment = (int) self::convertBase(\substr($this->binary, 52, 12), 2, 10);
    }
    
    /**
     * @property-read float      $timestamp
     * @property-read int        $workerID
     * @property-read int        $processID
     * @property-read int        $increment
     * @property-read string     $binary
     * @property-read \DateTime  $date
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
        
        $binary = \str_pad(self::convertBase($time, 10, 2), 42, 0, STR_PAD_LEFT).'0000100000'.\str_pad(self::convertBase((self::$incrementIndex++), 10, 2), 12, 0, STR_PAD_LEFT);
        return self::convertBase($binary, 2, 10);
    }
    
    /**
     * Is this a valid Snowflake or not? This does not determine if a given Snowflake exists in Discord.
     * @return boolean
     */
    function isValid() {
        return ($this->timestamp < \time() && $this->workerID >= 0 && $this->processID >= 0 && $this->increment >= 0 && $this->increment <= 4095);
    }
    
    /**
     * Converts numbers from one base to another.
     * @param string $input
     * @param int    $fromBase
     * @param int    $toBase
     */
    static function convertBase($input, $fromBase, $toBase) {
        if($fromBase === $toBase) {
            return $input;
        }
        
        $fromBaseArr = self::getBaseArray($fromBase);
        $toBaseArr = self::getBaseArray($toBase);
        
        $numberLen = \strlen($input);
        $number = \str_split($input, 1);
        
        $retval = '';
        
        if($toBase === 10) {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++) {
                $retval = \bcadd($retval, \bcmul(\array_search($number[($i - 1)], $fromBaseArr), \bcpow($fromBase, ($numberLen - $i))));
            }
            
            return $retval;
        }
        
        if($fromBase != 10) {
            $base10 = self::convertBase($input, $fromBase, 10);
        } else {
            $base10 = $input;
        }
        
        if($base10 < $toBase) {
            return $toBaseArr[$base10];
        }
        
        while($base10 != 0) {
            $retval = $toBaseArr[\bcmod($base10, $toBase)].$retval;
            $base10 = \bcdiv($base10, $toBase, 0);
        }
        
        return $retval;
    }
    
    /**
     * Return the valid values for a given base.
     * @param string|int $base
     * @return array
     */
    static private function getBaseArray($base) {
        switch((int) $base) {
            case 2:
                return array(0, 1);
            break;
            case 8:
                return array(0, 1, 2, 3, 4, 5, 6, 7);
            break;
            default:
            case 10:
                return array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
            break;
            case 16:
                return array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F');
            break;
        }
    }
}
>>>>>>> before discard
