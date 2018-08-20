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
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected static $loop;
    
    /**
     * @var \React\Filesystem\FilesystemInterface|null
     */
    private static $filesystem;
    
    /**
     * Sets the Event Loop.
     * @param \React\EventLoop\LoopInterface  $loop
     * @return void
     * @internal
     */
    static function setLoop(\React\EventLoop\LoopInterface $loop) {
        self::$loop = $loop;
        
        if(self::$filesystem === null) {
            $adapters = \React\Filesystem\Filesystem::getSupportedAdapters();
            if(!empty($adapters)) {
                self::$filesystem = \React\Filesystem\Filesystem::create($loop);
            }
        }
    }
    
    /**
     * Returns the stored React Filesystem instance, or null.
     * @return \React\Filesystem\FilesystemInterface|null
     */
    static function getFilesystem() {
        return self::$filesystem;
    }
    
    /**
     * Sets the React Filesystem instance.
     * @param \React\Filesystem\FilesystemInterface  $filesystem
     * @return void
     */
    static function setFilesystem(\React\Filesystem\FilesystemInterface $filesystem) {
        self::$filesystem = $filesystem;
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
            if(self::$filesystem !== null) {
                return self::$filesystem->getContents($file);
            }
            
            return \React\Promise\resolve(\file_get_contents($rfile));
        } elseif(\filter_var($file, FILTER_VALIDATE_URL)) {
            return \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file);
        }
        
        return \React\Promise\reject(new \RuntimeException('Given file is not resolvable'));
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
            return \preg_replace('/(```)/miu', "\\`\\`\\`", $text);
        }
        
        if($onlyInlineCode) {
            return \preg_replace('/(`)/miu', '\\\\$1', $text);
        }
        
        return \preg_replace('/(\\*|_|`|~)/miu', '\\\\$1', $text);
    }
    
    /**
     * Parses mentions in a text. Resolves with an array of `[ 'type' => string, 'ref' => Models ]` arrays, in the order they were parsed.
     * For mentions not available to this method or the client (e.g. mentioning a channel with no access to), `ref` will be the parsed mention (string).
     * Includes everyone and here mentions.
     *
     * @param \CharlotteDunois\Yasmin\Client|null  $client
     * @param string                               $content
     * @return \React\Promise\ExtendedPromiseInterface
     */
    static function parseMentions(?\CharlotteDunois\Yasmin\Client $client, string $content) {
        return (new \React\Promise\Promise(function (callable $resolve) use ($client, $content) {
            $bucket = array();
            $promises = array();
            
            \preg_match_all('/(?:<(@&|@!?|#|a?:.+?:)(\d+)>)|@everyone|@here/su', $content, $matches);
            foreach($matches[0] as $key => $val) {
                if($val === '@everyone') {
                    $bucket[$key] = array('type' => 'everyone', 'ref' => $val);
                } elseif($val === '@here') {
                    $bucket[$key] = array('type' => 'here', 'ref' => $val);
                } elseif($matches[1][$key] === '@&') {
                    $type = 'role';
                    
                    if($client) {
                        $role = $val;
                        
                        foreach($client->guilds as $guild) {
                            if($guild->roles->has($matches[2][$key])) {
                                $role = $guild->roles->get($matches[2][$key]);
                                break 1;
                            }
                        }
                        
                        $bucket[$key] = array('type' => $type, 'ref' => $role);
                    } else {
                        $bucket[$key] = array('type' => $type, 'ref' => $val);
                    }
                } elseif($matches[1][$key] === '#') {
                    $type = 'channel';
                    
                    if($client) {
                        if($client->channels->has($matches[2][$key])) {
                            $channel = $client->channels->get($matches[2][$key]);
                        } else {
                            $channel = $val;
                        }
                        
                        $bucket[$key] = array('type' => $type, 'ref' => $channel);
                    } else {
                        $bucket[$key] = array('type' => $type, 'ref' => $val);
                    }
                } elseif(\substr_count($matches[1][$key], ':') === 2) {
                    $type = 'emoji';
                    
                    if($client) {
                        $emoji = $val;
                        
                        foreach($client->guilds as $guild) {
                            if($guild->emojis->has($matches[2][$key])) {
                                $emoji = $guild->emojis->get($matches[2][$key]);
                                break 1;
                            }
                        }
                        
                        $bucket[$key] = array('type' => $type, 'ref' => $emoji);
                    } else {
                        $bucket[$key] = array('type' => $type, 'ref' => $val);
                    }
                } else {
                    $type = 'user';
                    
                    if($client) {
                        $promises[] = $client->fetchUser($matches[2][$key])->then(function (\CharlotteDunois\Yasmin\Models\User $user) use (&$bucket, $key, $type) {
                            $bucket[$key] = array('type' => $type, 'ref' => $user);
                        }, function () use (&$bucket, $key, $val, $type) {
                            $bucket[$key] = array('type' => $type, 'ref' => $val);
                        });
                    } else {
                        $bucket[$key] = array('type' => $type, 'ref' => $val);
                    }
                }
            }
            
            \React\Promise\all($promises)->done(function () use (&$bucket, $resolve) {
                $resolve($bucket);
            });
        }));
    }
    
    /**
     * Splits a string into multiple chunks at a designated character that do not exceed a specific length.
     *
     * Options will be merged into default split options (see Message), so missing elements will get added.
     * ```
     * array(
     *     'before' => string, (the string to add before the chunk)
     *     'after' => string, (the string to add after the chunk)
     *     'char' => string, (the string to split on)
     *     'maxLength' => int (the max. length of each chunk)
     * )
     * ```
     *
     * @param string  $text
     * @param array   $options  Options controlling the behaviour of the split.
     * @return string[]
     * @see \CharlotteDunois\Yasmin\Models\Message
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
                } elseif(\realpath($file)) {
                    $promises[] = self::resolveFileResolvable($file)->then(function ($data) use ($file) {
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
            
            if(isset($file['path'])) {
                if(filter_var($file['path'], \FILTER_VALIDATE_URL)) {
                    $promises[] = \CharlotteDunois\Yasmin\Utils\URLHelpers::resolveURLToData($file['path'])->then(function ($data) use ($file) {
                        $file['data'] = $data;
                        return $file;
                    });
                } elseif(self::$filesystem !== null) {
                    $promises[] = self::$filesystem->getContents($file['path'])->then(function ($data) use ($file) {
                        $file['data'] = $data;
                        return $file;
                    });
                } else {
                    $file['data'] = \file_get_contents($file['path']);
                    $promises[] = \React\Promise\resolve($file);
                }
            } else {
                $promises[] = \React\Promise\resolve($file);
            }
        }
        
        return \React\Promise\all($promises);
    }
    
    /**
     * Waits for a specific type of event to get emitted. Additional filter may be applied to look for a specific event (invoked as `$filter(...$args)`). Resolves with an array of arguments (from the event).
     *
     * Options may be:
     * ```
     * array(
     *     'time' => int, (if the event hasn't been found yet, this will define a timeout (in seconds) after which the promise gets rejected)
     * )
     * ```
     *
     * @param \CharlotteDunois\Events\EventEmitterInterface  $emitter
     * @param string                                         $event
     * @param callable|null                                  $filter
     * @param array                                          $options
     * @return \React\Promise\ExtendedPromiseInterface  This promise is cancellable.
     * @throws \RangeException          The exception the promise gets rejected with, if waiting times out.
     * @throws \OutOfBoundsException    The exception the promise gets rejected with, if the promise gets cancelled.
     */
    static function waitForEvent($emitter, string $event, ?callable $filter = null, array $options = array()) {
        $options['max'] = 1;
        $options['time'] = $options['time'] ?? 0;
        $options['errors'] = array('max');
        
        $collector = new \CharlotteDunois\Yasmin\Utils\Collector($emitter, $event, function (...$a) {
            return [ 0, $a ];
        }, $filter, $options);
        
        return $collector->collect()->then(function (\CharlotteDunois\Yasmin\Utils\Collection $bucket) {
            return $bucket->first();
        });
    }
}
