<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Utils;

class Snowflake { //TODO: Docs
    const EPOCH = 1420070400;
    static private $increment = 0;
    private $data = array();

    function __construct($snowflake) {
        $high = ($snowflake & 0xffffffff00000000) >> 32;
        $low = $snowflake & 0x00000000ffffffff;
        $binary = \pack('NN', $high, $low);
        
        $binary = self::convertBase($snowflake, '0123456789', '01');
        
        $binary = \str_pad($binary, 64, 0, STR_PAD_LEFT);
        $timestamp = \round(self::convertBase(\substr($binary, 0, 42), '01', '0123456789') / 1000) + self::EPOCH;

        $this->data = array(
            'timestamp' => $timestamp,
            'workerID' => (int) self::convertBase(\substr($binary, 42, 47), '01', '0123456789'),
            'processID' => (int) self::convertBase(\substr($binary, 47, 52), '01', '0123456789'),
            'increment' => (int) self::convertBase(\substr($binary, 52, 64), '01', '0123456789'),
            'binary' => $binary
        );
    }
    
    static function deconstruct($snowflake) {
        return (new self($snowflake));
    }
    
    static function generate() { //TODO
        if(self::$increment >= 4095) {
            self::$increment = 0;
        }
        
        $mtime = explode(' ', \microtime());
        $time = ((string) ((int) $mtime[1] - self::EPOCH)).\substr($mtime[0], 2, 5);
        
        $binary = \str_pad(self::convertBase($time, '0123456789', '01'), 42, 0, STR_PAD_LEFT).'0000100000'.\str_pad(self::convertBase((self::$increment++), '0123456789', '01'), 12, 0, STR_PAD_LEFT);
        return self::convertBase($binary, '01', '0123456789');
    }
    
    function getTimestamp() {
        return $this->data['timestamp'];
    }
    
    function getDate() {
        return (new \DateTime('@'.$this->data['timestamp']));
    }
    
    function getWorkerID() {
        return $this->data['workerID'];
    }
    
    function getProcessID() {
        return $this->data['processID'];
    }
    
    function getIncrement() {
        return $this->data['increment'];
    }
    
    function isValid() {
        return ($this->getTimestamp() < time() && $this->getWorkerID() >= 0 && $this->getProcessID() >= 0 && $this->getIncrement() >= 0 && $this->getIncrement() <= 4095);
    }
    
    static function convertBase($input, $fromBase, $toBase) {
        if($fromBase === $toBase) {
            return $input;
        }
        
        $numberLen = \strlen($input);
        $fromLen = \strlen($fromBase);
        $toLen = \strlen($toBase);
        
        $fromBaseArr = \str_split($fromBase, 1);
        $toBaseArr = \str_split($toBase, 1);
        $number = \str_split($input, 1);
        
        $retval = '';
        
        if($toBase == '0123456789') {
            $retval = 0;
            for ($i = 1; $i <= $numberLen; $i++) {
                $retval = \bcadd($retval, \bcmul(\array_search($number[($i - 1)], $fromBaseArr), \bcpow($fromLen, ($numberLen - $i))));
            }
            
            return $retval;
        }
        
        if($fromBase != '0123456789') {
            $base10 = self::convertBase($input, $fromBase, '0123456789');
        } else {
            $base10 = $input;
        }
        
        if($base10 < $toLen) {
            return $toBaseArr[$base10];
        }
        
        while($base10 != '0') {
            $retval = $toBaseArr[\bcmod($base10, $toLen)].$retval;
            $base10 = \bcdiv($base10, $toLen, 0);
        }
        
        return $retval;
    }
}
