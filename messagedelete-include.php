<?php
echo "[messageDelete]" . PHP_EOL;
//id, author, channel, guild, member
//createdAt, editedAt, createdTimestamp, editedTimestamp, content, cleanContent, attachments, embeds, mentions, pinned, type, reactions, webhookID
$message_content												= $message->content;
$message_id														= $message->id;
if ( ($message_content == NULL) || ($message_content == "") ){
	echo "BLANK MESSAGE DELETED" . PHP_EOL;
	return true;
} //Don't process blank messages, bots, or webhooks
$message_content_lower											= strtolower($message_content);

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
if ($GLOBALS['id'] == $author_user->id) return true; //Don't log messages that this bot deletes (This doesn't seem to work right now)

$author_username 												= $author_user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
$author_discriminator 											= $author_user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
$author_id 														= $author_user->id;												//echo "author_id: " . $author_id . PHP_EOL;
$author_avatar 													= $author_user->getAvatarURL();									//echo "author_avatar: " . $author_avatar . PHP_EOL;
$author_check 													= "$author_username#$author_discriminator"; 					//echo "author_check: " . $author_check . PHP_EOL;

//Load guild info
$guild				= $message->guild;
$author_guild_id	= $guild->id;
//Load config variables for the guild
$guild_folder = "\\guilds\\$author_guild_id";
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
include "$guild_config_path";


$modlog_channel			= $guild->channels->get($modlog_channel_id);

//Build the embed stuff
$log_message = "Message $message_id deleted from <#$author_channel_id>\n**Content:** $message_content" . PHP_EOL;
if (strlen($log_message) > 2048){
	$log_message ="Message $message_id deleted from <#$author_channel_id>";
	$data_string = "$message_content";
}
//		Build the embed
$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
$embed
//	->setTitle("$user_check")																// Set a title
	->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//	->setDescription("$author_guild_name")													// Set a description (below title, above fields)
	->setDescription("$log_message")														// Set a description (below title, above fields)
	//X days ago
	->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
//	->addField("Roles", 		"$author_role_name_queue_full")								// New line after this
	
	->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//	->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
	->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
	
	->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
	->setURL("");
//	Send the message
//	We do not need another promise here, so we call done, because we want to consume the promise
if ($modlog_channel){
	if ($data_string){ //Embed the changes as a text filew
		$modlog_channel->send('', array('embed' => $embed, 'files' => [['name' => "message.txt", 'data' => $data_string]]))->done(null, function ($error){
			echo $error.PHP_EOL; //Echo any errors
		});
	}else{
		$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
			echo $error.PHP_EOL; //Echo any errors
		});
	}
}
return true; //No more processing, we only want to process the first person mentioned
?>
