# Yasmin [![Build Status](https://scrutinizer-ci.com/g/CharlotteDunois/Yasmin/badges/build.png?b=master)](https://scrutinizer-ci.com/g/CharlotteDunois/Yasmin/build-status/master)

Yasmin is a Discord API library, which interacts with the HTTP REST API, but also with the WebSocket Real Time Gateway.

# Getting Started
Getting started with Yasmin is pretty trivial. All you need to do, is to use composer to install Yasmin and its dependencies.

```
composer require charlottedunois/yasmin
```

# Example
This is a fairly trivial example of using Yasmin.

```php
$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Yasmin\Client(array(), $loop);

$client->on('ready', function () use($client) {
    echo 'Logged in as '.$client->user->tag.' created on '.$client->user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
});

$client->on('message', function ($message) {
    echo 'Received Message from '.$message->author->tag.' in '.($message->channel->type === 'text' ? 'channel #'.$message->channel->name : 'DM').' with '.$message->attachments->count().' attachment(s) and '.\count($message->embeds).' embed(s)'.PHP_EOL;
});

$client->login('YOUR_TOKEN');
$loop->run();
```
