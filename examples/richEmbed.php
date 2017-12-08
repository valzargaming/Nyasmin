<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

/*
 * This example will demonstrate how to send a Rich Embed to a specific channel once ready.
 */

require_once(__DIR__.'/vendor/autoload.php');

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Yasmin\Client(array(), $loop);

$client->once('ready', function () use ($client) {
    try {
        $channel = $client->channels->get('CHANNEL_ID');
        
        // Making sure the channel exists
        if($channel) {
            $embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
            $embed->setTitle('A new Rich Embed')->setColor(random_int(0, 16777215))->setDescription(':)');
            
            $channel->send('', array('embed' => $embed))
                    ->otherwise(function ($error) {
                        echo $error.PHP_EOL;
                    });
        }
    } catch(\Exception $error) {
        // Handle exception
    }
});

$client->login('YOUR_TOKEN');
$loop->run();
