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
            
            $embed
                ->setTitle('A new Rich Embed')                                                       // Set a title
                ->setColor(random_int(0, 16777215))                                                  // Set a color (the thing on the left side)
                ->setDescription(':)')                                                               // Set a description (below title, above fields)
                ->addField('Test', 'Value')                                                          // Add one field
                ->addField('Test 2', 'Value 2', true)                                                // Add one inline field
                ->addField('Test 3', 'Value 3', true)                                                // Add another inline field
                ->setThumbnail('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')         // Set a thumbnail (the image in the top right corner)
                ->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             // Set an image (below everything except footer)
                ->setTimestamp()                                                                     // Set a timestamp (gets shown next to footer)
                ->setAuthor('Yasmin', 'https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')  // Set an author with icon
                ->setFooter('Generated with the Rich Embed Builder (Y)')                               // Set a footer without icon
                ->setURL('https://github.com/CharlotteDunois/Yasmin');                               // Set the URL
            
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
