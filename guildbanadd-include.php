<?php
$author_guild_id = $guild->id;
$author_guild_name = $guild->name;
$author_guild_avatar = $guild->getIconURL();

//Load config variables for the guild
$guild_folder = "\\guilds\\$author_guild_id";
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php";														//echo "guild_config_path: " . $guild_config_path . PHP_EOL;
if(!CheckFile($guild_folder, "guild_config.php")){
	$file = 'guild_config_template.php';
	if (!copy($file, $guild_config_path)){
		$message->reply("Failed to create guild_config file! Please contact <@116927250145869826> for assistance.");
	}//else $author_channel->send("<@$guild_owner_id>, I'm here! Please ;setup the bot.");
}
include "$guild_config_path"; //Configurable channel IDs, role IDs, and message IDs used in the guild for special functions
if($modlog_channel_id){
	$modlog_channel = $guild->channels->get($modlog_channel_id);
	//Get author variables
	$author_username = $user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
	$author_discriminator = $user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
	$author_id = $user->id;												//echo "author_id: " . $author_id . PHP_EOL;
	$author_check = "$author_username#$author_discriminator"; 
	$author_avatar = $user->getAvatarURL();	
	//Build the embed
	$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
	$embed
//		->setTitle("Roles")																		// Set a title
		->setColor("e1452d")																	// Set a color (the thing on the left side)
		->setDescription("$author_guild_name")													// Set a description (below title, above fields)
		->addField("Banned", "<@$author_id>")								// New line after this if ,true
		
		->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)	//		->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
		->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
		->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
		->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
		->setURL("");                             												// Set the URL
	//Send the message
	//We do not need another promise here, so we call done, because we want to consume the promise
	$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
		echo "[ERROR] $error".PHP_EOL; //Echo any errors
	});
}
?>
