<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

ini_set('xdebug.max_nesting_level', -1);
define('IN_DIR', str_replace('\\', '/', __DIR__));
require_once(IN_DIR.'/vendor/autoload.php');

$game = 'with Neko nya';
$timer = null;

$token = file_get_contents(IN_DIR.'/Yasmin.token');

$client = new \CharlotteDunois\Yasmin\Client(array(
    'ws.presence' => array(
        'game' => array(
            'name' => $game,
            'type' => 0,
            'url' => null
        )
    )
));

echo 'WS status is: '.$client->getWSstatus().PHP_EOL;

$client->on('debug', function ($debug) {
    echo $debug.PHP_EOL;
});
$client->on('error', function ($error) {
    echo $error.PHP_EOL;
});

$client->on('ready', function () use($client, $game, &$timer) {
    echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
    
    if($timer) {
        $client->cancelTimer($timer);
    }
    
    $user = $client->getClientUser();
    echo 'Logged in as '.$user->tag.' created on '.$user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
    
    $client->addPeriodicTimer(30, function () use ($user, $game) {
        $user->setGame($game.' | '.\bin2hex(\random_bytes(3)));
    });
    
    //$client->channels->get('323433852590751754')->send('Hello, my name is Yasmin!', array('files' => array('https://i.imgur.com/ML7aui6.png')))->done();
});
$client->on('disconnect', function ($code, $reason) use ($client, &$timer) {
    echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
    echo 'Disconnected! (Code: '.$code.' | Reason: '.$reason.')'.PHP_EOL;
    
    $timer = $client->addTimer(30, function ($client) {
        if($client->getWSstatus() === \CharlotteDunois\Yasmin\Constants::WS_STATUS_DISCONNECTED) {
            echo 'Connection forever lost'.PHP_EOL;
            $client->destroy();
        }
    });
});
$client->on('reconnect', function () use ($client) {
    echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
    echo 'Reconnect happening!'.PHP_EOL;
});

$client->on('message', function ($message) use ($client) {
    echo 'Received Message from '.$message->author->tag.' in channel #'.$message->channel->name.' with '.$message->attachments->count().' attachment(s) and '.\count($message->embeds).' embed(s)'.PHP_EOL;
    
    if($message->author->id === '200317799350927360') {
        if(\strpos($message->content, '#eval') === 0) {
            $code = \substr($message->content, 6);
            if(\substr($code, -1) !== ';') {
                $code .= ';';
            }
            
            if(\strpos($code, 'return') === false && \strpos($code, 'echo') === false) {
                $code = 'return '.$code;
            }
            
            (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($client, $code, $message) {
                while(@\ob_end_clean());
                
                $result = eval($code);
                
                if(!($result instanceof \React\Promise\Promise)) {
                    $result = \React\Promise\resolve($result);
                }
                
                $result->then(function ($result) use ($code, $message, $resolve, $reject) {
                    \ob_start('mb_output_handler');
                    \var_dump($result);
                    $result = @\ob_get_clean();
                    
                    $result = \explode("\n", \str_replace("\r", "", $result));
                    \array_shift($result);
                    $result = \implode(PHP_EOL, $result);
                    
                    if(\strlen($result) > 5000) {
                        $result = \substr($result, 0, 5000);
                    }
                    
                    while(@\ob_end_clean());
                    $message->channel->send($message->author.PHP_EOL.'```php'.PHP_EOL.$code.PHP_EOL.'```'.PHP_EOL.'Result:'.PHP_EOL.'```'.PHP_EOL.$result.PHP_EOL.'```', array('split' => array('before' => "```\n", 'after' => "\n```")))->then($resolve, $reject);
                }, $reject);
            }))->then(function () { }, function ($e) use ($code, $message) {
                while(@\ob_end_clean());
                $message->channel->send($message->author.PHP_EOL.'```php'.PHP_EOL.$code.PHP_EOL.'```'.PHP_EOL.'Error: ```'.PHP_EOL.$e.PHP_EOL.'```', array('split' => array('before' => "```\n", 'after' => "\n```")));
            });
        }
    }
});

$client->login($token)->done(function () use ($client) {
    $client->addPeriodicTimer(60, function ($client) {
        echo 'Avg. Ping is '.$client->getPing().'ms'.PHP_EOL;
    });
    
    $client->addTimer(3600, function ($client) {
        echo 'Ending session'.PHP_EOL;
        $client->destroy()->then(function () use ($client) {
            echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
        });
    });
});

$client->getLoop()->run();
