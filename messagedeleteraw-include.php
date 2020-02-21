<?php
echo "messageDeleteRaw" . PHP_EOL;		
$channel_id				= $channel->id;
$log_message = "Message with id $message_id was deleted from <#$channel_id>\n" . PHP_EOL;

//		Build the embed
$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
$embed
	->setColor("a7c5fd")																	// Set a color (the thing on the left side)
	->setDescription("$log_message")														// Set a description (below title, above fields)
	->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
	->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
	->setURL("");
//		Send the message
//		We do not need another promise here, so we call done, because we want to consume the promise
$guild					= $channel->guild;
$author_guild_id		= $guild->id;
//Load config variables for the guild
$guild_folder = "\\guilds\\$author_guild_id";
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
include "$guild_config_path";

$modlog_channel			= $guild->channels->get($modlog_channel_id);
if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
	echo $error.PHP_EOL; //Echo any errors
});
return true; //No more processing, we only want to process the first person mentioned
?>