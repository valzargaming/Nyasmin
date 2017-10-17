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

$client = new \CharlotteDunois\Yasmin\client;

$client->on('debug', function ($event) {
    echo $event->getParam(0).PHP_EOL;
});

$client->on('ready', function () use($client) {
    echo 'We are ready!'.PHP_EOL;
    
    $user = $client->getClientUser();
    echo 'Logged in as '.$user->tag.' created on '.$user->createdAt->format('d.m.Y H:i:s').' (avatar url: '.$user->getAvatarURL().')'.PHP_EOL;
});
$client->on('disconnect', function ($event) {
    list($code, $reason) = $event->getParams();
    echo 'Disconnected! (Code: '.$code.' | Reason: '.$reason.')'.PHP_EOL;
});
$client->on('reconnect', function () {
    echo 'Reconnect happening!'.PHP_EOL;
});

$client->login($token)->done(function () use ($client) {
    $client->getLoop()->addPeriodicTimer(60, function () use ($client) {
        echo 'Avg. Ping is '.$client->getPing().'ms'.PHP_EOL;
    });
});

$client->getLoop()->addTimer(180, function () use ($client) {
    echo 'Ending session'.PHP_EOL;
    $client->wsmanager()->disconnect();
    $client->getLoop()->stop();
});

$client->getLoop()->run();
