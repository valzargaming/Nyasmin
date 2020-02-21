<?php
$type				= $data_array['type'];
$tts				= $data_array['tts'];
$timestamp			= $data_array['timestamp'];
$pinned				= $data_array['pinned'];
$nonce				= $data_array['nonce'];
$mentions			= $data_array['mentions'];
$mention_roles		= $data_array['mention_roles'];
$mention_everyone	= $data_array['mention_everyone'];
$member				= $data_array['member']; //echo "member: " . var_dump($member) . PHP_EOL; //username, id, discriminator,avatar
$id					= $data_array['id']; //echo "id: " . var_dump($id) . PHP_EOL; //username, id, discriminator,avatar
$flags				= $data_array['flags'];
$embeds				= $data_array['embeds'];
$edited_timestamp	= $data_array['edited_timestamp'];
$content			= $data_array['content'];
$channel_id			= $data_array['channel_id'];
$author				= $data_array['author']; //echo "author: " . var_dump($author) . PHP_EOL; //username, id, discriminator,avatar
$attachments		= $data_array['attachments'];
$guild_id			= $data_array['guild_id'];

$guild				= $channel->guild;
$author_guild_id = $guild->id;
//Load config variables for the guild
$guild_folder = "\\guilds\\$author_guild_id";
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
include "$guild_config_path";

$modlog_channel		= $guild->channels->get($modlog_channel_id);

$log_message = "[Link](https://discordapp.com/channels/$author_guild_id/$author_channel_id/$message_id_new)
**Channel:** <#$channel_id>
**Message ID:** $id
**New content:** $content" . PHP_EOL;
$channel->fetchMessage($id)->then(function($message) use ($modlog_channel, $log_message){	//Resolve the promise
	//Load author info
	$author_user													= $message->author; //User object
	$author_channel 												= $message->channel;
	$author_channel_id												= $author_channel->id; 											//echo "author_channel_id: " . $author_channel_id . PHP_EOL;
	$author_channel_class											= get_class($author_channel);
	$is_dm = false;
	if ($author_channel_class === "CharlotteDunois\Yasmin\Models\DMChannel"){ //True if direct message
		$is_dm = true;
		return true; //Don't process DMs
	}
	
	$author_username 												= $author_user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
	$author_discriminator 											= $author_user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
	$author_id 														= $author_user->id;												//echo "author_id: " . $author_id . PHP_EOL;
	$author_avatar 													= $author_user->getAvatarURL();									//echo "author_avatar: " . $author_avatar . PHP_EOL;
	$author_check 													= "$author_username#$author_discriminator"; 					//echo "author_check: " . $author_check . PHP_EOL;
	
//			Build the embed
	$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
	$embed
//				->setTitle("$user_check")																// Set a title
		->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//				->setDescription("$author_guild_name")												// Set a description (below title, above fields)
//				->setDescription("")														// Set a description (below title, above fields)
		->setAuthor("$author_check ($author_id)", $author_avatar)  											// Set an author with icon
		->addField("Uncached Message Update", 		"$log_message")				// New line after this
		
//				->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
		->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
		
		->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
		->setURL("");
//			Send the message
//			We do not need another promise here, so we call done, because we want to consume the promise
	if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
		echo $error.PHP_EOL; //Echo any errors
	});
	return true; //No more processing, we only want to process the first person mentioned
});
?>