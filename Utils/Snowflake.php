<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Utils;

/**
 * Represents a Snowflake.
 */
class Snowflake { //TODO: 64bit
    /**
     * @var int  Time since UNIX epoch to Discord epoch.
     */
    const EPOCH = 1420070400;
    
    static private $increment = 0;
    private $data = array();
    
    /**
     * Loads a snowflake and returns some information about it.
     * @param string $snowflake
     */
    function __construct(string $snowflake) {
        $binary = \str_pad(self::convertBase($snowflake, 10, 2), 64, 0, STR_PAD_LEFT);
        $timestamp = (int) \round(self::convertBase(\substr($binary, 0, 42), 2, 10) / 1000) + self::EPOCH;

        $this->data = array(
            'timestamp' => $timestamp,
            'workerID' => (int) self::convertBase(\substr($binary, 42, 5), 2, 10),
            'processID' => (int) self::convertBase(\substr($binary, 47, 5), 2, 10),
            'increment' => (int) self::convertBase(\substr($binary, 52, 12), 2, 10),
            'binary' => $binary
        );
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
        if(self::$increment >= 4095) {
            self::$increment = 0;
        }
        
        $mtime = \explode('.', (string) \microtime(true));
        $time = ((string) (((int) $mtime[0]) - self::EPOCH)).\substr($mtime[1], 0, 3);
        
        $binary = \str_pad(self::convertBase($time, 10, 2), 42, 0, STR_PAD_LEFT).'0000100000'.\str_pad(self::convertBase((self::$increment++), 10, 2), 12, 0, STR_PAD_LEFT);
        return self::convertBase($binary, 2, 10);
    }
    
    /**
     * Get the timestamp of when this Snowflake was created. Returns a float with milliseconds.
     * @return float
     */
    function getTimestamp() {
        return $this->data['timestamp'];
    }
    
    /**
     * Get the DateTime of when this Snowflake was created.
     * @return \DateTime
     */
    function getDate() {
        return (new \DateTime('@'.((int) $this->data['timestamp'])));
    }
    
    /**
     * Get the worker ID.
     * @return int
     */
    function getWorkerID() {
        return $this->data['workerID'];
    }
    
    /**
     * Get the process ID.
     * @return int
     */
    function getProcessID() {
        return $this->data['processID'];
    }
    
    /**
     * Get the increment value.
     * @return int
     */
    function getIncrement() {
        return $this->data['increment'];
    }
    
    /**
     * Is this a valid Snowflake or not? This does not determine if a given Snowflake exists in Discord.
     * @return boolean
     */
    function isValid() {
        return ($this->getTimestamp() < \time() && $this->getWorkerID() >= 0 && $this->getProcessID() >= 0 && $this->getIncrement() >= 0 && $this->getIncrement() <= 4095);
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
        
        if($toBase == 10) {
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
        
        while($base10 != '0') {
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
