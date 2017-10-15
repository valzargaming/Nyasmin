<?php
/**
 * Neko Cord
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

$token = file_get_contents("Z:\\Eigene Dokumente\\Discord Bots\\Charuru Commando\\storage\\CharuruAlpha.token");

define('IN_DIR', str_replace('\\', '/', __DIR__));

spl_autoload_register(function ($name) {
    if(strpos($name, 'CharlotteDunois\\NekoCord') === 0) {
        $name = str_replace('CharlotteDunois\\NekoCord\\', '', $name);
        $name = str_replace('\\', '/', $name);
        
        //echo IN_DIR.'/'.$name.'.php '.(file_exists(IN_DIR.'/'.$name.'.php') ? '(true)' : '(false)').PHP_EOL;
        if(file_exists(IN_DIR.'/'.$name.'.php')) {
            include_once(IN_DIR.'/'.$name.'.php');
            return true;
        }
    }
});
require_once(IN_DIR.'/vendor/autoload.php');

$client = new \CharlotteDunois\NekoCord\Client();

$client->on('raw', function ($event) {
    $packet = $event->getParam(0);
    echo 'RAW: '.$packet['op'].' ('.\CharlotteDunois\NekoCord\Constants::$opcodesNumber[$packet['op']].') '.($packet['t'] ?? '').PHP_EOL;
});
$client->on('ready', function () {
    echo 'We are ready!'.PHP_EOL;
});
$client->on('disconnect', function () {
    echo 'Disconnected!'.PHP_EOL;
});
$client->on('reconnect', function () {
    echo 'Reconnect happening!'.PHP_EOL;
});

$client->login($token)->done(function () use ($client) {
    $loop = $client->getLoop();
    
    $loop->addPeriodicTimer(60, function () use($client) {
        echo 'Avg. Ping is '.$client->getPing().'ms'.PHP_EOL;
    });
    
    $loop->run();
});
