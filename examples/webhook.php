<?php
/**
 * Yasmin
 * Copyright 2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

/*
 * This example will demonstrate how to create a webhook client and execute the webhook.
 */

require_once(__DIR__.'/vendor/autoload.php');

$loop = \React\EventLoop\Factory::create();
$webhook = new \CharlotteDunois\Yasmin\WebhookClient('WEBHOOK_ID', 'WEBHOOK_TOKEN', array(), $loop);

$webhook->send('Hallo')->then(function () use ($loop) {
    echo 'Message sent!'.PHP_EOL;
    $loop->stop();
}, function ($error) {
    echo $error.PHP_EOL;
});

$loop->run();
