<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

$token = file_get_contents("Z:\\Eigene Dokumente\\Discord Bots\\Charuru Commando\\storage\\CharuruAlpha.token");

define('IN_DIR', str_replace('\\', '/', __DIR__));

spl_autoload_register(function ($name) {
    if(strpos($name, 'CharlotteDunois\\Yasmin') === 0) {
        $name = str_replace('CharlotteDunois\\Yasmin\\', '', $name);
        $name = str_replace('\\', '/', $name);
        
        if(file_exists(IN_DIR.'/'.$name.'.php')) {
            include_once(IN_DIR.'/'.$name.'.php');
            return true;
        }
    }
});
require_once(IN_DIR.'/vendor/autoload.php');

$client = new \CharlotteDunois\Yasmin\Client();

echo 'WS status is: '.$client->getWSstatus().PHP_EOL;

$client->on('debug', function ($debug) {
    echo $debug.PHP_EOL;
});
$client->on('error', function ($error) {
    echo $error.PHP_EOL;
});

$client->on('ready', function () use($client) {
    echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
    
    $user = $client->getClientUser();
    echo 'Logged in as '.$user->tag.' created on '.$user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
    
    $user->setGame('with Yasmin');
});
$client->on('disconnect', function ($code, $reason) {
    echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
    echo 'Disconnected! (Code: '.$code.' | Reason: '.$reason.')'.PHP_EOL;
});
$client->on('reconnect', function () {
    echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
    echo 'Reconnect happening!'.PHP_EOL;
});

$client->login($token)->done(function () use ($client) {
    $loop = $client->getLoop();
    
    $timer = $loop->addPeriodicTimer(60, function () use ($client) {
        echo 'Avg. Ping is '.$client->getPing().'ms'.PHP_EOL;
    });
    
    /*$loop->addTimer(5, function () use ($client) {
        //var_dump($client->channels);
        //var_dump($client->guilds);
        //var_dump($client->presences);
        //var_dump($client->users);
        
        echo 'Making API request...'.PHP_EOL;
        $client->apimanager()->endpoints->getGuild('270679409126670337')->then(function ($response) {
            var_dump($response);
        }, function ($error) {
            var_dump($error);
        });
    });*/
    
    $loop->addTimer(500, function () use ($client, $timer) {
        $timer->cancel();
        
        echo 'Ending session'.PHP_EOL;
        $client->destroy()->then(function () use ($client) {
            echo 'WS status is: '.$client->getWSstatus().PHP_EOL;
        });
    });
});

$client->getLoop()->run();
