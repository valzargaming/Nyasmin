<?php
/**
 * Yasmin
 * Copyright 2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

/*
 * This example will demonstrate how to listen on the message event and reply to the message,
 * if the message mentions the bot and says hi.
 */

require_once(__DIR__.'/vendor/autoload.php');

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Yasmin\Client(array(
    'ws.disabledEvents' => array(
        /* We disable the TYPING_START event to save CPU cycles, we don't need it here in this example. */
        'TYPING_START'
    )
), $loop);

$client->on('message', function ($message) use ($client) {
    try {
        if($message->mentions->users->has($client->user->id)) {
            $args = explode(' ', $message->content);
            if(mb_strtolower($args[1]) === 'hi') {
                $message->reply('Hi!')->otherwise(function ($error) {
                    echo $error.PHP_EOL;
                });
            }
        }
    } catch(\Exception $error) {
        // Handle exception
    }
});

$client->login('YOUR_TOKEN');
$loop->run();
