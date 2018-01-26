# Yasmin [![Build Status](https://scrutinizer-ci.com/g/CharlotteDunois/Yasmin/badges/build.png?b=master)](https://scrutinizer-ci.com/g/CharlotteDunois/Yasmin/build-status/master)

Yasmin is a Discord API library, which interacts with the HTTP REST API, but also with the WebSocket Real Time Gateway.

This library is **only** for PHP 7.1 (and later) and use in CLI. Only bot accounts are supported by Yasmin.

# Before you start
Before you start using this Library, you **need** to know how PHP works, you need to know the language and you need to know how Event Loops and Promises work. This is a fundamental requirement before you start. Without this knowledge, you will only suffer.

# Getting Started
Getting started with Yasmin is pretty straight forward. All you need to do is to use [composer](https://packagist.org/packages/charlottedunois/yasmin) to install Yasmin and its dependencies. After that, you can include composer's autoloader into your file and start interacting with Discord and Yasmin!

```
composer require charlottedunois/yasmin
```

<br>

**Important Information**: All properties on class instances, which are implemented using a magic method (which means pretty much all properties), are **throwing** if the property doesn't exist.

# Example
This is a fairly trivial example of using Yasmin. You should put all your listener code into try-catch blocks and handle exceptions accordingly.

```php
// Include composer autoloader

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Yasmin\Client(array(), $loop);

$client->on('ready', function () use ($client) {
    echo 'Logged in as '.$client->user->tag.' created on '.$client->user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
});

$client->on('message', function ($message) {
    echo 'Received Message from '.$message->author->tag.' in '.($message->channel->type === 'text' ? 'channel #'.$message->channel->name : 'DM').' with '.$message->attachments->count().' attachment(s) and '.\count($message->embeds).' embed(s)'.PHP_EOL;
});

$client->login('YOUR_TOKEN');
$loop->run();
```

# Voice Support
There is no voice support, as there are many technical reasons against it.

# Documentation
https://charlottedunois.github.io/Yasmin/

# Issues
If you think something is wrong, or not working as expected, then try to listen on the `error` event. This event gets emitted when an error inside the library (or event listener) gets caught. Make sure you also have a rejection handler for all promises, as unhandled promise rejections get swallowed. Feel free to open an issue with as much information as you can get.

# Need help? Join the Yasmin or Discord API guild.

[![](https://discordapp.com/api/guilds/389502182065700876/embed.png?style=banner1)](https://discord.gg/hUpnqam) [![](https://discordapp.com/api/guilds/81384788765712384/embed.png?style=banner1)](https://discord.gg/MzjnGFF)
