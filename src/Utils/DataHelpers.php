<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Utils;

/**
 * Data Helper methods.
 */
class DataHelpers {
    private static $loop;
    
    /**
     * Sets the Event Loop.
     * @param \React\EventLoop\LoopInterface  $loop
     * @internal
     */
    static function setLoop(\React\EventLoop\LoopInterface $loop) {
        self::$loop = $loop;
    }
    
    /**
     * Resolves a color to an integer.
     * @param array|int|string  $color
     * @return int
     * @throws \InvalidArgumentException
     */
    static function resolveColor($color) {
        if(\is_int($color)) {
            return $color;
        }
        
        if(!\is_array($color)) {
            return \hexdec(((string) $color));
        }
        
        if(\count($color) < 1) {
            throw new \InvalidArgumentException('Color "'.\var_export($color, true).'" is not resolvable');
        }
        
        return (($color[0] << 16) + (($color[1] ?? 0) << 8) + ($color[2] ?? 0));
    }
    
    /**
     * Makes a DateTime instance from an UNIX timestamp and applies the default timezone.
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
    
    /**
     * Resolves filepath and URL into file data - returns it if it's neither. Resolves with a string.
     * @param string  $file
     * @return \React\Promise\ExtendedPromiseInterface
     */
    static function resolveFileResolvable(string $file) {
        $rfile = @\realpath($file);
        if($rfile) {
            $promise = \React\Promise\resolve(\file_get_contents($rfile));
        } elseif(\filter_var($file, FILTER_VALIDATE_URL)) {
            $promise = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file);
        } else {
            $promise = \React\Promise\resolve($file);
        }
        
        return $promise;
    }
    
    /**
     * Escapes any Discord-flavour markdown in a string.
     * @param string  $text            Content to escape.
     * @param bool    $onlyCodeBlock   Whether to only escape codeblocks (takes priority).
     * @param bool    $onlyInlineCode  Whether to only escape inline code.
     * @return string
     */
    static function escapeMarkdown(string $text, bool $onlyCodeBlock = false, bool $onlyInlineCode = false) {
        if($onlyCodeBlock) {
            return \preg_replace('/```/miu', "`\u{200b}``", $text);
        }
        
        if($onlyInlineCode) {
            return \preg_replace('/(`|\\\)/miu', '\\$1', \preg_replace('/\\(`|\\)/miu', '\\$1', $text));
        }
        
        return \preg_replace('/(\*|_|`|~|\\\)/miu', '\\$1', \preg_replace('/\\(\*|_|`|~|\\)/miu', '\\$1', $text));
    }
    
    /**
     * Splits a string into multiple chunks at a designated character that do not exceed a specific length.
     * @param string  $text
     * @param array   $options  Options controlling the behaviour of the split.
     * @return string[]
     */
    static function splitMessage(string $text, array $options = array()) {
        $options = \array_merge(\CharlotteDunois\Yasmin\Models\Message::DEFAULT_SPLIT_OPTIONS, $options);
        
        if(\mb_strlen($text) > $options['maxLength']) {
            $i = 0;
            $messages = array();
            
            $parts = \explode($options['char'], $text);
            foreach($parts as $part) {
                if(!isset($messages[$i])) {
                    $messages[$i] = '';
                }
                
                if((\mb_strlen($messages[$i]) + \mb_strlen($part) + 2) >= $options['maxLength']) {
                    $i++;
                    $messages[$i] = '';
                }
                
                $messages[$i] .= $part.$options['char'];
            }
            
            return $messages;
        }
        
        return array($text);
    }
    
    /**
     * Resolves files of Message Options.
     * @param array $options
     * @return \React\Promise\ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    static function resolveMessageOptionsFiles(array $options) {
        if(empty($options['files'])) {
            return \React\Promise\resolve(array());
        }
        
        $promises = array();
        foreach($options['files'] as $file) {
            if($file instanceof \CharlotteDunois\Yasmin\Models\MessageAttachment) {
                $file = $file->_getMessageFilesArray();
            }
            
            if(\is_string($file)) {
                if(\filter_var($file, \FILTER_VALIDATE_URL)) {
                    $promises[] = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file)->then(function ($data) use ($file) {
                        return array('name' => \basename($file), 'data' => $data);
                    });
                } else {
                    $promises[] = \React\Promise\resolve(array('name' => 'file-'.\bin2hex(\random_bytes(3)).'.jpg', 'data' => $file));
                }
                
                continue;
            }
            
            if(!\is_array($file)) {
                continue;
            }
            
            if(!isset($file['data']) && !isset($file['path'])) {
                throw new \InvalidArgumentException('Invalid file array passed, missing data and path, one is required');
            }
            
            if(!isset($file['name'])) {
                if(isset($file['path'])) {
                    $file['name'] = \basename($file['path']);
                } else {
                    $file['name'] = 'file-'.\bin2hex(\random_bytes(3)).'.jpg';
                }
            }
            
            if(isset($file['path']) && filter_var($file['path'], \FILTER_VALIDATE_URL)) {
                $promises[] = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file['path'])->then(function ($data) use ($file) {
                    $file['data'] = $data;
                    return $file;
                });
            } else {
                $promises[] = \React\Promise\resolve($file);
            }
        }
        
        return \React\Promise\all($promises);
    }
    
    /**
     * Waits for a specific event to get emitted. Additional filter may be applied to look for a specific event (invoked as <code>$filter(\.\.\.$args)</code>). Resolves with an array of arguments (from the event).
     *
     * Options may be:
     * <pre>
     * array(
     *     'time' => int (if the event hasn't been found yet, this will define a timeout (in seconds) after which the promise gets rejected)
     * )
     * </pre>
     *
     * @param \CharlotteDunois\Events\EventEmitterInterface  $emitter
     * @param string                                         $event
     * @param callable|null                                  $filter
     * @param array                                          $options
     * @return \React\Promise\ExtendedPromiseInterface  This promise is cancelable.
     * @throws \RangeException
     */
    static function waitForEvent(\CharlotteDunois\Events\EventEmitterInterface $emitter, string $event, ?callable $filter = null, array $options = array()) {
        $listener = null;
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use (&$listener, $emitter, $event, $filter, $options) {
            if(!empty($options['time'])) {
                $timer = self::$loop->addTimer(((int) $options['time']), function () use ($emitter, $event, &$listener, $reject) {
                    $emitter->removeListener($event, $listener);
                    $reject(new \RangeException('Waiting for event took too long'));
                });
            } else {
                $timer = null;
            }
            
            $listener = function (...$args) use ($emitter, $event, $filter, &$listener, &$timer, $resolve, $reject) {
                if($filter) {
                    try {
                        if($filter(...$args)) {
                            if($timer) {
                                $timer->cancel();
                            }
                            
                            $emitter->removeListener($event, $listener);
                            $resolve($args);
                        }
                        
                        return;
                    } catch(\Throwable | \Exception | \Error $e) {
                        $emitter->removeListener($event, $listener);
                        return $reject($e);
                    }
                }
                
                if($timer) {
                    $timer->cancel();
                }
                
                $emitter->removeListener($event, $listener);
                $resolve($args);
            };
            
            $emitter->on($event, $listener);
        }, function (callable $resolve, callable $reject) use (&$listener, $emitter, $event) {
            $emitter->removeListener($event, $listener);
            $reject(new \OutOfBoundsException('Operation cancelled'));
        }));
    }
}
