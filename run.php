<?php
//This file was written by Valithor#5947 <@116927250145869826>
//Special thanks to keira#7829 <@297969955356540929> for helping me get this behemoth working after converting from DiscordPHP

//DO NOT VAR_DUMP GETS, most objects like GuildMember have a guild property which references all members
//Use get_class($object) to verify the main object (usually a collection, check src/Models/)
//Use get_class($object->first())to verify you're getting the right kind of object. IE, $author_guildmember->roles should be Models\Role)
//If any of these methods resolve to a class of React\Promise\Promise you're probably passing an invalid parameter for the class
//Always subtract 1 when counting roles because everyone has an @everyone role

include __DIR__.'/vendor/autoload.php';
define('MAIN_INCLUDED', 1); 	//Token and SQL credential files are protected, this must be defined to access
ini_set('memory_limit', '-1'); 	//Unlimited memory usage

//Global variables
include 'config.php'; 			//Global config variables
include 'species.php';			//Used by the species role picker function
include 'sexualities.php';		//Used by the sexuality role picker function
include 'gender.php';			//Used by the gender role picker function
include 'custom_roles.php';		//Create your own roles with this template!

use charlottedunois\yasmin;
$loop = \React\EventLoop\Factory::create();
$discord = new \CharlotteDunois\Yasmin\Client(array(), $loop);

/*
set_exception_handler(function (Throwable $e) {
    // reconnect, log uncaught, etc etc
});
*/
 
$discord->on('disconnect', function($erMsg, $code){ //Automatically reconnect if the bot disconnects due to inactivity (Not tested)
    echo "----- BOT DISCONNECTED FROM DISCORD WITH CODE $code FOR REASON: $erMsg -----" . PHP_EOL;
	echo "RESTARTING BOT" . PHP_EOL;
	$restart_cmd = 'cmd /c "'. __DIR__  . '\run.bat"'; //echo $restart_cmd . PHP_EOL;
	system($restart_cmd);
	//die;
});

$discord->once('ready', function () use ($discord){	// Listen for events here
	echo "SETUP" . PHP_EOL;
	$line_count = COUNT(FILE(basename($_SERVER['PHP_SELF'])));
	$version = "RC V1.0.0";
	
	//Set status
	$discord->user->setPresence(
		array(
			'since' => null, //unix time (in milliseconds) of when the client went idle, or null if the client is not idle
			'game' => array(
				//'name' => "over the Palace $version",
				'name' => "$line_count lines of code! $version",
				'type' => 3, //0, 1, 2, 3, 4 | Game/Playing, Streaming, Listening, Watching, Custom Status
				'url' => null //stream url, is validated when type is 1, only Youtube and Twitch allowed
				/*
				Bots are only able to send name, type, and optionally url.
				As bots cannot send states or emojis, they can't make effective use of custom statuses.
				The header for a "Custom Status" may show up on their profile, but there is no actual custom status, because those fields are ignored.
				*/
			),
			'status' => 'dnd', //online, dnd, idle, invisible, offline
			'afk' => false
		)
	);
	echo 'Logged in as '.$discord->user->tag.' created on '.$discord->user->createdAt->format('d.m.Y H:i:s').PHP_EOL;
	$timestampSetup = time();
	echo "timestampSetup: " . $timestampSetup . PHP_EOL;
	//Save this to a file to be loaded, used in messageUpdate
	
	$discord->on('message', function ($message){ //Handling of a message
		$message_content = $message->content;
		if ( ($message_content == NULL) || ($message_content == "") ) return true; //Don't process blank messages, bots, or webhooks
		$message_content_lower = strtolower($message_content);
		/*
		*********************
		*********************
		Required includes
		*********************
		*********************
		*/
		
		include_once "custom_functions.php";
		include "constants.php"; //Redeclare $now every time
		
		//Load author data from message
		$author_user													= $message->author; //User object
		$author_channel 												= $message->channel;
		$author_channel_id												= $author_channel->id; 											//echo "author_channel_id: " . $author_channel_id . PHP_EOL;
		$author_channel_class											= get_class($author_channel);
		$is_dm															= false;
		if ($author_channel_class === "CharlotteDunois\Yasmin\Models\DMChannel") //True if direct message
		$is_dm															= true;
		$author_username 												= $author_user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
		$author_discriminator 											= $author_user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$author_id 														= $author_user->id;												//echo "author_id: " . $author_id . PHP_EOL;
		$author_avatar 													= $author_user->getAvatarURL();									//echo "author_avatar: " . $author_avatar . PHP_EOL;
		$author_check 													= "$author_username#$author_discriminator"; 					//echo "author_check: " . $author_check . PHP_EOL;
		
		/*
		*********************
		*********************
		Get the guild and guildmember collections for the author
		*********************
		*********************
		*/
		
		if ($is_dm === false){ //Guild message
			$author_guild 												= $author_channel->guild;
			$author_guild_id 											= $author_guild->id; 											//echo "discord_guild_id: " . $author_guild_id . PHP_EOL;
			$author_guild_name											= $author_guild->name;
			$guild_owner_id												= $author_guild->ownerID;

			$guild_folder = "\\guilds\\$author_guild_id";
//			Create a folder for the guild if it doesn't exist already
            if(!CheckDir($guild_folder)){
				if(!CheckFile($guild_folder, "guild_owner_id.php")){
					VarSave($guild_folder, "guild_owner_id.php", $guild_owner_id);
				}else $guild_owner_id = VarLoad($guild_folder, "guild_owner_id.php");
			}
			if ($guild_owner_id == $author_id){
				$owner = true; //Enable usage of restricted commands
			} else $owner = false;
			
			//Load config variables for the guild
			$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php";														//echo "guild_config_path: " . $guild_config_path . PHP_EOL;
			if(!CheckFile($guild_folder, "guild_config.php")){
				$file = 'guild_config_template.php';
				if (!copy($file, $guild_config_path)){
					$message->reply("Failed to create guild_config file! Please contact <@116927250145869826> for assistance.");
				}else $author_channel->send("<@$guild_owner_id>, I'm here! Please ;setup the bot.");
			}
			include "$guild_config_path"; //Configurable channel IDs, role IDs, and message IDs used in the guild for special functions
			
			$author_guild_avatar 										= $author_guild->getIconURL();
			$author_guild_roles 										= $author_guild->roles; 								//Role object for the guild
			if($getverified_channel_id) 	$getverified_channel 		= $author_guild->channels->get($getverified_channel_id);
//			if($verifylog_channel_id) 		$verify_channel 			= $author_guild->channels->get($verifylog_channel_id);
			if($watch_channel_id) 			$watch_channel 				= $author_guild->channels->get($watch_channel_id);
			if($modlog_channel_id) 			$modlog_channel 			= $author_guild->channels->get($modlog_channel_id);
			if($general_channel_id) 		$general_channel			= $author_guild->channels->get($general_channel_id);
			if($suggestion_pending_channel_id) 	$suggestion_pending_channel		= $author_guild->channels->get($suggestion_pending_channel_id);
			if($suggestion_approved_channel_id) $suggestion_approved_channel	= $author_guild->channels->get($suggestion_approved_channel_id);
			$author_member 												= $author_guild->members->get($author_id); 				//GuildMember object
			$author_member_roles 										= $author_member->roles; 								//Role object for the author);
		}else{ //Direct message
			if ($author_check != 'Palace Bot#9203'){
				GLOBAL $server_invite;
				echo "DIRECT MESSAGE - NO PROCESSING OF FUNCTIONS ALLOWED" . PHP_EOL;			
				$dm_text = "Please use commands for this bot within a server.";
				$message->reply("$dm_text \n$server_invite");
			}
			return true;
		}
		
		/*
		*********************
		*********************
		Options
		*********************
		*********************
		*/
		if(!CheckFile($guild_folder, "command_symbol.php")){
														//Author must prefix text with this to use commands
		}else 											$command_symbol = VarLoad($guild_folder, "command_symbol.php");			//Load saved option file (Not used yet, but might be later)
		

//		Chat options
		GLOBAL $react_option, $vanity_option, $nsfw_option;
		if(!CheckFile($guild_folder, "react_option.php"))
																$react	= $react_option;								//Bot will not react to messages if false
		else 													$react 	= VarLoad($guild_folder, "react_option.php");			//Load saved option file
		if(!CheckFile($guild_folder, "vanity_option.php"))
																$vanity	= $vanity_option;								//Allow SFW vanity like hug, nuzzle, kiss
		else 													$vanity = VarLoad($guild_folder, "vanity_option.php");			//Load saved option file
		if(!CheckFile($guild_folder, "nsfw_option.php"))
																$nsfw	= $nsfw_option;									//Allow NSFW commands
		else 													$nsfw 	= VarLoad($guild_folder, "nsfw_option.php");				//Load saved option file
		
//		Role picker options		
		GLOBAL $rolepicker_option, $species_option, $sexuality_option, $gender_option, $custom_option;		
		
		if ( ($rolepicker_id != "") || ($rolepicker_id != NULL) ){
			if(!CheckFile($guild_folder, "rolepicker_option.php")){
																$rp0	= $rolepicker_option;							//Allow Rolepicker
			}else 												$rp0	= VarLoad($guild_folder, "rolepicker_option.php");
			if ( ($species_message_id != "") || ($species_message_id != NULL) ){
				if(!CheckFile($guild_folder, "species_option.php")){
																$rp1	= $species_option;								//Species role picker
				}else 											$rp1	= VarLoad($guild_folder, "species_option.php");
			} else												$rp1	= false;
			if ( ($sexuality_message_id != "") || ($species_message_id != NULL) ){
				if(!CheckFile($guild_folder, "sexuality_option.php")){
																$rp2	= $sexuality_option;							//Sexuality role picker
				}else 											$rp2	= VarLoad($guild_folder, "sexuality_option.php");
			} else												$rp2	= false;
			if ( ($gender_message_id != "") || ($gender_message_id != NULL) ){
				if(!CheckFile($guild_folder, "gender_option.php")){
																$rp3	= $gender_option;								//Gender role picker
				}else 											$rp3	= VarLoad($guild_folder, "gender_option.php");
			} else $rp3 = false;
			if ( ($customroles_message_id != "") || ($gender_message_id != NULL) ){
				if(!CheckFile($guild_folder, "customrole_option.php"))
																$rp4	= $custom_option;								//Custom role picker
				else 											$rp4	= VarLoad($guild_folder, "customrole_option.php");
			}else												$rp4	= false;
		}else{ //All functions are disabled
																$rp0 	= false;
																$rp1 	= false;
																$rp2 	= false;
																$rp3 	= false;
																$rp4 	= false;
		}
		
		echo "Message from $author_check <$author_id> <#$author_channel_id>: {$message_content}", PHP_EOL;
		$author_webhook = $author_user->webhook;
		if ($author_webhook === true) return true; //Don't process webhooks
		/*

		*********************
		*********************
		Load persistent variables for author
		*********************
		*********************
		*/
		
		$author_folder = $guild_folder."\\".$author_id;
		CheckDir($author_folder); //Check if folder exists and create if it doesn't
		if(CheckFile($author_folder, "watchers.php")){
			echo "AUTHOR IS BEING WATCHED" . PHP_EOL;
			$watchers = VarLoad($author_folder, "watchers.php");
//			echo "WATCHERS: "; var_dump($watchers); //array of user IDs
			$null_array = true;
			foreach ($watchers as $watcher){
				if ($watcher != NULL){																									//echo "watcher: " . $watcher . PHP_EOL;
					$null_array = false; //mark the array as valid
					try{
//						Get objects for the watcher
						$watcher_member = $author_guild->members->get($watcher);													//echo "watcher_member class: " . get_class($watcher_member) . PHP_EOL;
						$watcher_user = $watcher_member->user;																		//echo "watcher_user class: " . get_class($watcher_user) . PHP_EOL;
						$watcher_user->createDM()->then(function($watcher_dmchannel) use ($message){	//Promise
//							echo "watcher_dmchannel class: " . get_class($watcher_dmchannel) . PHP_EOL; //DMChannel
							if($watcher_dmchannel) $watcher_dmchannel->send("<@{$message->author->id}> sent a message in <#{$message->channel->id}>: \n{$message->content}");
							return true;
						});
					}catch(Exception $e){
//						RuntimeException: Unknown property
					}
				}
			}
			if($null_array){ //Delete the null file
				VarDelete($author_folder, "watchers.php");
				echo 'AUTHOR IS NO LONGER BEING WATCHED BY ANYONE' . PHP_EOL;
			}
		}
		
		/*
		*********************
		*********************
		Guild-specific variables
		*********************
		*********************
		*/
		
		$creator_name									= "Valithor";;
		$creator_discriminator							= "5947";
		$creator_id										= "116927250145869826";
		$creator_check									= "$creator_name#$creator_discriminator";
		if($author_check != $creator_check) $creator	= false;
		else 								$creator 	= true;
		
		
		$adult 		= false;
//		$owner		= false;
		$dev		= false;
		$admin 		= false;
		$mod		= false;
		$verified	= false;
		$bot		= false;
		$vzgbot		= false;
		$muted		= false;
		
		$author_guild_roles_names 				= array(); 												//Names of all guild roles
		$author_guild_roles_ids 				= array(); 												//IDs of all guild roles
		foreach ($author_guild_roles as $role){
			$author_guild_roles_names[] 		= $role->name; 																		//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
			$author_guild_roles_ids[] 			= $role->id; 																		//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
		}																															//echo "discord_guild_roles_names" . PHP_EOL; var_dump($author_guild_roles_names);
																																	//echo "discord_guild_roles_ids" . PHP_EOL; var_dump($author_guild_roles_ids);
		/*
		*********************
		*********************
		Get the guild-related collections for the author
		*********************
		*********************
		*/
//		Populate arrays of the info we need
		$author_member_roles_names 										= array();
		$author_member_roles_ids 										= array();
		$x=0;
		foreach ($author_member_roles as $role){
			if ($x!=0){ //0 is always @everyone so skip it
				$author_member_roles_names[] 							= $role->name; 												//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
				$author_member_roles_ids[]								= $role->id; 												//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
				if ($role->id == $role_18_id)			$adult 			= true;							//Author has the 18+ role
				if ($role->id == $role_dev_id)    		$dev 			= true;							//Author has the dev role
				if ($role->id == $role_owner_id)    	$owner	 		= true;							//Author has the owner role
				if ($role->id == $role_admin_id)		$admin 			= true;							//Author has the admin role
				if ($role->id == $role_mod_id)			$mod 			= true;							//Author has the mod role
				if ($role->id == $role_verified_id)		$verified 		= true;							//Author has the verified role
				if ($role->id == $role_bot_id)			$bot 			= true;							//Author has the bot role
				if ($role->id == $role_vzgbot_id)		$vzgbot 		= true;							//Author is this bot
				if ($role->id == $role_muted_id)		$muted 			= true;							//Author is this bot
			}
			$x++;
		}
		if ($creator || $owner)	$bypass = true;
		else					$bypass = false;
		
		/*
		*********************
		*********************
		Owner setup command (NOTE: Changes made here will not affect servers using a manual config file)
		*********************
		*********************
		*/
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'setup'){ //;setup	
			$documentation = $documentation . "`currentsetup` send DM with current settings.\n";
			//Roles
			$documentation = $documentation . "\n**Roles:**\n";
			$documentation = $documentation . "`setup dev @role`\n";
			$documentation = $documentation . "`setup admin @role`\n";
			$documentation = $documentation . "`setup mod @role`\n";
			$documentation = $documentation . "`setup bot @role`\n";
			$documentation = $documentation . "`setup vzg @role` (Role with the name Palace Bot, not the actual bot)\n";
			$documentation = $documentation . "`setup muted @role`\n";
			$documentation = $documentation . "`setup verified @role`\n";
			$documentation = $documentation . "`setup adult @role`\n";
			//User
			$documentation = $documentation . "**Users:**\n";
			$documentation = $documentation . "`setup rolepicker @user` The user who posted the rolepicker messages\n";
			//Channels
			$documentation = $documentation . "**Channels:**\n";
			$documentation = $documentation . "`setup general #channel` The primary chat channel, also welcomes new users to everyone\n";
			$documentation = $documentation . "`setup welcome #channel` Simple welcome message tagging new user\n";
			$documentation = $documentation . "`setup welcomelog #channel` Detailed message about the user\n";
			$documentation = $documentation . "`setup log #channel` Detailed log channel\n";
//			$documentation = $documentation . "`setup verify #channel` Detailed log channel\n"; //Not currently implemented
			$documentation = $documentation . "`setup watch #channel` ;watch messages are duplicated here instead of in a DM\n";
			$documentation = $documentation . "`setup rolepicker channel #channel` Where users pick a role\n";
			//Messages
			$documentation = $documentation . "**Messages:**\n";
			$documentation = $documentation . "`setup species messageid`\n";
			$documentation = $documentation . "`setup species2 messageid`\n";
			$documentation = $documentation . "`setup sexuality messageid`\n";
			$documentation = $documentation . "`setup gender messageid`\n";
			$documentation = $documentation . "`setup customroles messageid`\n";
			
			$documentation_sanitized = str_replace("*","",$documentation);
			$documentation_sanitized = str_replace("_","",$documentation_sanitized);
			$documentation_sanitized = str_replace("`","",$documentation_sanitized);
			$documentation_sanitized = str_replace("\n","",$documentation_sanitized);
			$doc_length = strlen($documentation_sanitized);
			if ($doc_length < 1025){
 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
					->setTitle("Setup commands for $author_guild_name")														// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
					->setDescription("$documentation")														// Set a description (below title, above fields)
//					->addField("⠀", "$documentation")														// New line after this			
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
//				Open a DM channel then send the rich embed message
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
					echo 'SEND ;SETUP EMBED' . PHP_EOL;
					return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "error: " . $error . PHP_EOL; //Echo any errors
					});
				});
				return true;
			}else{
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
					echo 'SEND ;SETUP MESSAGE' . PHP_EOL;
					$author_dmchannel->send($documentation);
				});
				return true;
			}
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'currentsetup'){ //;currentsetup
			//send DM with current settings
			//Roles
			$documentation = $documentation . "\n**Roles:**\n";
			$documentation = $documentation . "`dev @role` $role_dev_id\n";
			$documentation = $documentation . "`admin @role` $role_admin_id\n";
			$documentation = $documentation . "`mod @role` $role_mod_id\n";
			$documentation = $documentation . "`bot @role` $role_bot_id\n";
			$documentation = $documentation . "`vzg @role` $role_vzgbot_id\n";
			$documentation = $documentation . "`muted @role` $role_muted_id\n";
			$documentation = $documentation . "`verified @role` $role_verified_id\n";
			$documentation = $documentation . "`adult @role` $role_18_id\n";
			//User
			$documentation = $documentation . "**Users:**\n";
			$documentation = $documentation . "`rolepicker @user` $rolepicker_id\n";
			//Channels
			$documentation = $documentation . "**Channels:**\n";
			$documentation = $documentation . "`general #channel` $general_channel_id\n";
			$documentation = $documentation . "`welcome #channel` $welcome_public_channel_id\n";
			$documentation = $documentation . "`welcomelog #channel` $welcome_log_channel_id\n";
			$documentation = $documentation . "`log #channel` $modlog_channel_id\n";
//			$documentation = $documentation . "`verify #channel`\n"; //Not currently implemented
			$documentation = $documentation . "`watch #channel` $watch_channel_id\n";
			$documentation = $documentation . "`rolepicker channel #channel` $rolepicker_channel_id\n";
			//Messages
			$documentation = $documentation . "**Messages:**\n";
			$documentation = $documentation . "`species messageid` $species_message_id\n";
			$documentation = $documentation . "`species2 messageid` $species2_message_id\n";
			$documentation = $documentation . "`sexuality messageid` $sexuality_message_id\n";
			$documentation = $documentation . "`gender messageid` $gender_message_id\n";
			$documentation = $documentation . "`customroles messageid` $customrole_message_id\n";
			
			$documentation_sanitized = str_replace("*","",$documentation);
			$documentation_sanitized = str_replace("_","",$documentation_sanitized);
			$documentation_sanitized = str_replace("`","",$documentation_sanitized);
			$documentation_sanitized = str_replace("\n","",$documentation_sanitized);
			$doc_length = strlen($documentation_sanitized);
			if ($doc_length < 1025){
 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
					->setTitle("Current setup for $author_guild_name")														// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
					->setDescription("$documentation")														// Set a description (below title, above fields)
//					->addField("⠀", "$documentation")														// New line after this			
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
//				Open a DM channel then send the rich embed message
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
					echo 'SEND ;CURRENTSETUP EMBED' . PHP_EOL;
					return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "error: " . $error . PHP_EOL; //Echo any errors
					});
				});
				return true;
			}else{
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
					echo 'SEND ;CURRENTSETUP MESSAGE' . PHP_EOL;
					$author_dmchannel->send($documentation);
				});
				return true;
			}
		}
		
		//Roles
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 11) == $command_symbol . 'setup dev '){
			$filter = "$command_symbol" . "setup dev ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_dev_id.php", $value);
				$message->reply("Developer role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 13) == $command_symbol . 'setup admin '){
			$filter = "$command_symbol" . "setup admin ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_admin_id.php", $value);
				$message->reply("Admin role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 11) == $command_symbol . 'setup mod '){
			$filter = "$command_symbol" . "setup mod ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_mod_id.php", $value);
				$message->reply("Moderator role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 11) == $command_symbol . 'setup bot '){
			$filter = "$command_symbol" . "setup bot ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_bot_id.php", $value);
				$message->reply("Bot role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 14) == $command_symbol . 'setup vzgbot '){
			$filter = "$command_symbol" . "setup vzgbot ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_vzgbot_id.php", $value);
				$message->reply("Palace Bot role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 13) == $command_symbol . 'setup muted '){
			$filter = "$command_symbol" . "setup muted ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			echo "value: '$value';" . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "role_muted_id.php", $value);
				$message->reply("Muted role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 16) == $command_symbol . 'setup verified '){
			$filter = "$command_symbol" . "setup verified ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_verified_id.php", $value);
				$message->reply("Verified role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 13) == $command_symbol . 'setup adult '){
			$filter = "$command_symbol" . "setup adult ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@&", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "role_18_id.php", $value);
				$message->reply("Adult role ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the role");
			return true;
		}
		
		//Channels
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 15) == $command_symbol . 'setup general '){
			$filter = "$command_symbol" . "setup general ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "general_channel_id.php", $value);
				$message->reply("General channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 15) == $command_symbol . 'setup welcome '){
			$filter = "$command_symbol" . "setup welcome ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "welcome_public_channel_id.php", $value);
				$message->reply("Welcome channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 18) == $command_symbol . 'setup welcomelog '){
			$filter = "$command_symbol" . "setup welcomelog ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "welcome_log_channel_id.php", $value);
				$message->reply("Welcome log channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 11) == $command_symbol . 'setup log '){
			$filter = "$command_symbol" . "setup log ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "modlog_channel_id.php", $value);
				$message->reply("Log channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 14) == $command_symbol . 'setup verify '){
			$filter = "$command_symbol" . "setup verify ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "getverified_channel_id.php", $value);
				$message->reply("Verify channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 13) == $command_symbol . 'setup watch '){
			$filter = "$command_symbol" . "setup watch ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "watch_channel_id.php", $value);
				$message->reply("Watch channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}		
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 26) == $command_symbol . 'setup rolepicker channel '){
			$filter = "$command_symbol" . "setup rolepicker channel ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "rolepicker_channel_id.php", $value);
				$message->reply("Rolepicker channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 34) == $command_symbol . 'setup suggestion pending channel '){
			$filter = "$command_symbol" . "setup suggestion pending channel";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "suggestion_pending_channel_id.php", $value);
				$message->reply("Suggestion pending channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 35) == $command_symbol . 'setup suggestion approved channel '){
			$filter = "$command_symbol" . "setup suggestion approved channel ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value); echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "suggestion_approved_channel_id.php", $value);
				$message->reply("Suggestion approved channel ID saved!");
			}else $message->reply("Invalid input! Please enter a channel ID or <#mention> a channel");
			return true;
		}

		//Users
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 18) == $command_symbol . 'setup rolepicker '){
			$filter = "$command_symbol" . "setup rolepicker ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
			$value = str_replace(">", "", $value);
			$value = trim($value); echo "value: " . $value . PHP_EOL;
			if(is_numeric($value)){
				VarSave($guild_folder, "rolepicker_id.php", $value);
				$message->reply("Rolepicker user ID saved!");
			}else $message->reply("Invalid input! Please enter an ID or @mention the user");
			return true;
		}		
		
		//Messages
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 15) == $command_symbol . 'setup species '){
			$filter = "$command_symbol" . "setup species ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "species_message_id.php", $value);
				$message->reply("Species message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 16) == $command_symbol . 'setup species2 '){
			$filter = "$command_symbol" . "setup species2 ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "species2_message_id.php", $value);
				$message->reply("Species2 message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 17) == $command_symbol . 'setup sexuality '){
			$filter = "$command_symbol" . "setup sexuality ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "sexuality_message_id.php", $value);
				$message->reply("Sexuality message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 14) == $command_symbol . 'setup gender '){
			$filter = "$command_symbol" . "setup gender ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "gender_message_id.php", $value);
				$message->reply("Gender message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		
		if ($creator || $owner)
		if (substr($message_content_lower, 0, 19) == $command_symbol . 'setup customroles '){
			$filter = "$command_symbol" . "setup customrole ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "customroles_message_id.php", $value);
				$message->reply("Custom roles message ID saved!");
			}else $message->reply("Invalid input! Please enter a message ID");
			return true;
		}
		
		
		/*
		*********************
		*********************
		Help command
		*********************
		*********************
		*/	
		
		if ($message_content_lower == $command_symbol . 'help'){ //;help
			$documentation = "**Command symbol: $command_symbol**\n";
			if($creator || $owner){ //toggle options
				$documentation = $documentation . "\n__**Owner:**__\n";
				//toggle options
				$documentation = $documentation . "*Bot settings:*\n";
				//react
				$documentation = $documentation . "`react`\n";
				//vanity
				$documentation = $documentation . "`vanity`\n";
				//nsfw
				$documentation = $documentation . "`nsfw`\n";
				//rolepicker
				$documentation = $documentation . "`rolepicker`\n";
				//species
				$documentation = $documentation . "`species`\n";
				//species2
				$documentation = $documentation . "`species2`\n";
				//sexuality
				$documentation = $documentation . "`sexuality`\n";
				//gender
				$documentation = $documentation . "`gender`\n";
				//customrole
				$documentation = $documentation . "`customrole`\n";
				
				
				//TODO:
				//join
				//leave
				//tempmute/tm
			}
			if($creator || $owner || $dev || $admin){
				$documentation = $documentation . "\n__**High Staff:**__\n";
				//current settings
				$documentation = $documentation . "`settings` sends a DM with current settings.\n";
				//v
				$documentation = $documentation . "`v` or `verify` gives the verified role.\n";
				//cv
				$documentation = $documentation . "`cv` or `clearv` clears the verification channel and posts a short notice.\n";
				//clearall
				$documentation = $documentation . "`clearall` clears the current channel of up to 100 messages.\n";
				//watch
				$documentation = $documentation . "`watch` sends a direct message to the author whenever the mentioned sends a message.\n";
				//unwatch
				$documentation = $documentation . "`unwatch` removes the effects of the watch command.\n";
				//vwatch
				$documentation = $documentation . "`vw` or `vwatch` gives the verified role to the mentioned and watches them.\n";
				//warn
				$documentation = $documentation . "`warn` logs an infraction.\n";
				//infractions
				$documentation = $documentation . "`infractions` replies with a list of infractions for someone.\n";
				//removeinfraction
				$documentation = $documentation . "`removeinfraction @mention #`\n";
				//kick
				$documentation = $documentation . "`kick @mention reason`\n";
				//ban
				$documentation = $documentation . "`ban @mention reason`\n";
				//Strikeout invalid options
				if ( ($role_muted_id != "") || ($role_muted_id != NULL) ) $documentation = $documentation . "~~"; //Strikeout invalid options
				//unmute
				$documentation = $documentation . "`unmute @mention reason`\n";
				//Strikeout invalid options
				if ( ($role_muted_id != "") || ($role_muted_id != NULL) ) $documentation = $documentation . "~~"; //Strikeout invalid options
				
			}
			if($creator || $owner || $dev || $admin || $mod){
				$documentation = $documentation . "\n__**Moderators:**__\n";
				//Strikeout invalid options
				if ( ($role_muted_id != "") || ($role_muted_id != NULL) ) $documentation = $documentation . "~~"; //Strikeout invalid options
				//mute/m
				$documentation = $documentation . "`mute @mention reason`\n";
				//Strikeout invalid options
				if ( ($role_muted_id != "") || ($role_muted_id != NULL) ) $documentation = $documentation . "~~"; //Strikeout invalid options
				//whois
				$documentation = $documentation . "`whois @mention`\n";
				
			}
			if($vanity){
				$documentation = $documentation . "\n__**Vanity commands:**__\n";
				//hug/snuggle
				$documentation = $documentation . "`hug` or `snuggle`\n";
				//kiss/smooch
				$documentation = $documentation . "`kiss` or `smooch`\n";
				//nuzzle
				$documentation = $documentation . "`nuzzle`\n";
				//boop
				$documentation = $documentation . "`boop`\n";
				//bap
				$documentation = $documentation . "`bap`\n";
			}
			if($nsfw && $adult){
				//TODO
			}
			//All other functions
			$documentation = $documentation . "\n__**General:**__\n";
			//ping
			$documentation = $documentation . "`ping` replies with 'Pong!'\n";
			//roles / roles @
			$documentation = $documentation . "`roles` displays the roles for the author or user being mentioned.\n";
			//avatar
			$documentation = $documentation . "`avatar` displays the profile picture of the author or user being mentioned.\n";
			$documentation_sanitized = str_replace("*","",$documentation);
			$documentation_sanitized = str_replace("_","",$documentation_sanitized);
			$documentation_sanitized = str_replace("`","",$documentation_sanitized);
			$documentation_sanitized = str_replace("\n","",$documentation_sanitized);
			$doc_length = strlen($documentation_sanitized);
			if ($doc_length < 1025){
 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
					->setTitle("Commands for $author_guild_name")											// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
					->setDescription("$documentation")														// Set a description (below title, above fields)
//					->addField("⠀", "$documentation")														// New line after this			
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
//				Open a DM channel then send the rich embed message
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
					echo 'SEND ;HELP EMBED' . PHP_EOL;
					return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
						echo "error: " . $error . PHP_EOL; //Echo any errors
					});
				});
				return true;
			}else{
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
					echo 'SEND ;HELP MESSAGE' . PHP_EOL;
					$author_dmchannel->send($documentation);
				});
				return true;
			}
		}
		
		if($creator || $owner || $dev || $admin)
		if ($message_content_lower == $command_symbol . 'settings'){ //;settings
			$documentation = "Command symbol: $command_symbol\n";
			$documentation = $documentation . "\nBot options:\n";
			//react
			$documentation = $documentation . "`react:` ";
			if ($react) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			//vanity
			$documentation = $documentation . "`vanity:` ";
			if ($vanity) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			//nsfw
			$documentation = $documentation . "`nsfw:` ";
			if ($nsfw) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			//rolepicker
			$documentation = $documentation . "`\nrolepicker:` ";
			if ($rp0) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			
			//Strikeout invalid options
			if (!$rp0) $documentation = $documentation . "~~"; //Strikeout invalid options
			
			//species
			$documentation = $documentation . "`species:` ";
			if ($rp1) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			//sexuality
			$documentation = $documentation . "`sexuality:` ";
			if ($rp2) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			//gender
			$documentation = $documentation . "`gender:` ";
			if ($rp3) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			//customrole
			$documentation = $documentation . "`customrole:` ";
			if ($rp4) $documentation = $documentation . "**Enabled**\n";
			else $documentation = $documentation . "**Disabled**\n";
			
			//Strikeout invalid options
			if (!$rp0) $documentation = $documentation . "~~"; //Strikeout invalid options
			
			$documentation_sanitized = str_replace("*","",$documentation);
			$documentation_sanitized = str_replace("_","",$documentation_sanitized);
			$documentation_sanitized = str_replace("`","",$documentation_sanitized);
			$doc_length = strlen($documentation_sanitized);
			if ($doc_length < 1025){
 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
					->setTitle("Settings for $author_guild_name")											// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
					->setDescription("$documentation")														// Set a description (below title, above fields)
//					->addField("⠀", "$documentation")														// New line after this
					
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
//				Open a DM channel then send the rich embed message
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
					echo 'SEND ;SETTINGS EMBED' . PHP_EOL;
					return $author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
						echo $error.PHP_EOL; //Echo any errors
					});
				});
				return true;
			}else{
				$author_user->createDM()->then(function($author_dmchannel) use ($message, $documentation){	//Promise
					echo 'SEND ;SETTINGS MESSAGE' . PHP_EOL;
					$author_dmchannel->send($documentation);
				});
				return true;
			}
		}
		
		
		
		/*
		*********************
		*********************
		Creator/Owner option functions
		*********************
		*********************
		*/
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'react'){ //toggle reaction functions ;react
//			echo "react: $react" . PHP_EOL;
			if(!CheckFile($guild_folder, "react_option.php")){
				VarSave($guild_folder, "react_option.php", $react);
//				echo "NEW REACT FILE" . PHP_EOL;
			}
//			VarLoad
			$react_var = VarLoad($guild_folder, "react_option.php");
//			echo "react_var: $react_var" . PHP_EOL;
//			VarSave
			$react_flip = !$react_var;
//			echo "react_flip: $react_flip" . PHP_EOL;
			VarSave($guild_folder, "react_option.php", $react_flip);
			if ($react_flip === true)
				$message->reply("Reaction functions enabled!");
			else $message->reply("Reaction functions disabled!");
			return true;
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'vanity'){ //toggle vanity functions ;vanity
			if(!CheckFile($guild_folder, "vanity_option.php")){
				VarSave($guild_folder, "vanity_option.php", $vanity);														//echo "NEW VANITY FILE" . PHP_EOL;
			}
			$vanity_var = VarLoad($guild_folder, "vanity_option.php");														//echo "vanity_var: $vanity_var" . PHP_EOL;
			$vanity_flip = !$vanity_var;																			//echo "vanity_flip: $vanity_flip" . PHP_EOL;
			VarSave($guild_folder, "vanity_option.php", $vanity_flip);
			if ($vanity_flip === true)
				$message->reply("Vanity functions enabled!");
			else $message->reply("Vanity functions disabled!");
			return true;
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'nsfw'){ //toggle nsfw functions ;nsfw
//			echo "nsfw: $nsfw" . PHP_EOL;
			if(!CheckFile($guild_folder, "nsfw_option.php")){
				VarSave($guild_folder, "nsfw_option.php", $nsfw);
//				echo "NEW NSFW FILE" . PHP_EOL;
			}
			$nsfw_var = VarLoad($guild_folder, "nsfw_option.php");															//echo "nsfw_var: $nsfw_var" . PHP_EOL;
			$nsfw_flip = !$nsfw_var;																				//echo "nsfw_flip: $nsfw_flip" . PHP_EOL;
			VarSave($guild_folder, "nsfw_option.php", $nsfw_flip);
			if ($nsfw_flip === true)
				$message->reply("NSFW functions enabled!");
			else $message->reply("NSFW functions disabled!");
			return true;
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'rolepicker'){ //toggle rolepicker ;rolepicker
			//echo "rp0: $rp0" . PHP_EOL;
			if(!CheckFile($guild_folder, "rolepicker_option.php")){
				VarSave($guild_folder, "rolepicker_option.php", $nsfw);
				echo "NEW ROLEPICKER FILE" . PHP_EOL;
			}
			$rolepicker_var = VarLoad($guild_folder, "rolepicker_option.php");															//echo "nsfw_var: $nsfw_var" . PHP_EOL;
			$rolepicker_flip = !$rolepicker_var;																				//echo "nsfw_flip: $nsfw_flip" . PHP_EOL;
			VarSave($guild_folder, "rolepicker_option.php", $rolepicker_flip);
			if ($rolepicker_flip === true)
				$message->reply("Rolepicker enabled!");
			else $message->reply("Rolepicker disabled!");
			return true;
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'species'){ //toggle species ;species
			//echo "rp1: $rp1" . PHP_EOL;
			if(!CheckFile($guild_folder, "species_option.php")){
				VarSave($guild_folder, "species_option.php", $nsfw);
				echo "NEW SPECIES FILE" . PHP_EOL;
			}
			$species_var = VarLoad($guild_folder, "species_option.php");															//echo "nsfw_var: $nsfw_var" . PHP_EOL;
			$species_flip = !$species_var;																				//echo "nsfw_flip: $nsfw_flip" . PHP_EOL;
			VarSave($guild_folder, "species_option.php", $species_flip);
			if ($species_flip === true)
				$message->reply("Species roles enabled!");
			else $message->reply("Species roles	disabled!");
			return true;
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'sexuality'){ //toggle sexuality ;sexuality
			echo "rp2: $rp2" . PHP_EOL;
			if(!CheckFile($guild_folder, "sexuality_option.php")){
				VarSave($guild_folder, "sexuality_option.php", $nsfw);
				echo "NEW SEXUALITY FILE" . PHP_EOL;
			}
			$sexuality_var = VarLoad($guild_folder, "sexuality_option.php");															//echo "nsfw_var: $nsfw_var" . PHP_EOL;
			$sexuality_flip = !$sexuality_var;																				//echo "nsfw_flip: $nsfw_flip" . PHP_EOL;
			VarSave($guild_folder, "sexuality_option.php", $sexuality_flip);
			if ($sexuality_flip === true)
				$message->reply("Sexuality roles enabled!");
			else $message->reply("Sexuality roles disabled!");
			return true;
		}
				
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'gender'){ //toggle gender ;gender
			//echo "rp3: $rp3" . PHP_EOL;
			if(!CheckFile($guild_folder, "gender_option.php")){
				VarSave($guild_folder, "gender_option.php", $nsfw);
				echo "NEW GENDER FILE" . PHP_EOL;
			}
			$gender_var = VarLoad($guild_folder, "gender_option.php");															//echo "nsfw_var: $nsfw_var" . PHP_EOL;
			$gender_flip = !$gender_var;																				//echo "nsfw_flip: $nsfw_flip" . PHP_EOL;
			VarSave($guild_folder, "gender_option.php", $gender_flip);
			if ($gender_flip === true)
				$message->reply("Gender roles enabled!");
			else $message->reply("Gender roles disabled!");
			return true;
		}
		
		if ($creator || $owner)
		if ($message_content_lower == $command_symbol . 'customroles'){ //toggle custom roles ;customroles
			//echo "rp4: $rp4" . PHP_EOL;
			if(!CheckFile($guild_folder, "custom_option.php")){
				VarSave($guild_folder, "custom_option.php", $nsfw);
				echo "NEW CUSTOM ROLE FILE" . PHP_EOL;
			}
			$custom_var = VarLoad($guild_folder, "custom_option.php");															//echo "nsfw_var: $nsfw_var" . PHP_EOL;
			$custom_flip = !$custom_var;																				//echo "nsfw_flip: $nsfw_flip" . PHP_EOL;
			VarSave($guild_folder, "custom_option.php", $custom_flip);
			if ($custom_flip === true)
				$message->reply("Custom roles enabled!");
			else $message->reply("Custom roles disabled!");
			return true;
		}
		
		
		/*
		*********************
		*********************
		Gerneral command functions
		*********************
		*********************
		*/
		
		if ($message_content_lower == $command_symbol . 'ping'){
			$message->reply("Pong!");
			return true;
		}
		
		if ($message_content_lower == $command_symbol . '18+'){
			if ($adult){
				if($react) $message->react("👍");
				$message->reply("You have the 18+ role!");
			}else{
				if($react) $message->react("👎");
				$message->reply("You do NOT have the 18+ role!");
			}
			return true;
		}
		
		if ($message_content_lower == $command_symbol . 'roles'){ //;roles
			echo "GETTING ROLES FOR AUTHOR" . PHP_EOL;
//			Build the string for the reply
			$author_role_name_queue 									= "";
//			$author_role_name_queue_full 								= "Here's a list of roles for you:" . PHP_EOL;
			foreach ($author_member_roles_ids as $author_role){
				$author_role_name_queue 								= "$author_role_name_queue<@&$author_role> ";
			}
			$author_role_name_queue 									= substr($author_role_name_queue, 0, -1);
			$author_role_name_queue_full 								= PHP_EOL . $author_role_name_queue;
//			Send the message
			if($react) $message->react("👍");
//			$message->reply($author_role_name_queue_full . PHP_EOL);
//			Build the embed
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
//				->setTitle("Roles")																		// Set a title
				->setColor("a7c5fd")																	// Set a color (the thing on the left side)
				->setDescription("$author_guild_name")												// Set a description (below title, above fields)
				->addField("Roles", 		"$author_role_name_queue_full")								// New line after this if ,true
				
				->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
//			Send the message
//			We do not need another promise here, so we call done, because we want to consume the promise
			$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
				echo $error.PHP_EOL; //Echo any errors
			});
			return true; //No more processing, we only want to process the first person mentioned
		}
		
		if (substr($message_content_lower, 0, 7) == $command_symbol . 'roles '){//;roles @
			echo "GETTING ROLES FOR MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "unmute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			//$mention_role_name_queue_full								= "Here's a list of roles for the requested users:" . PHP_EOL;
			$mention_role_name_queue_default							= "";
//			$mentions_arr_check = (array)$mentions_arr;																					//echo "mentions_arr_check: " . PHP_EOL; var_dump ($mentions_arr_check); //Shows the collection object
//			$mentions_arr_check2 = empty((array) $mentions_arr_check);																	//echo "mentions_arr_check2: " . PHP_EOL; var_dump ($mentions_arr_check2); //Shows the collection object			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
				
//				Get the roles of the mentioned user
				$target_guildmember 									= $message->guild->members->get($mention_id); 	//This is a GuildMember object
				$target_guildmember_role_collection 					= $target_guildmember->roles;					//This is the Role object for the GuildMember
				
//				Get the avatar URL of the mentioned user
				$target_guildmember_user								= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
				$mention_avatar 										= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);
				
//				Populate arrays of the info we need
//				$target_guildmember_roles_names 						= array();
				$target_guildmember_roles_ids 							= array(); //Not being used here, but might as well grab it
				$x=0;
				foreach ($target_guildmember_role_collection as $role){
					if ($x!=0){ //0 is @everyone so skip it
//						$target_guildmember_roles_names[] 				= $role->name; 													//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
						$target_guildmember_roles_ids[] 				= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
					}
					$x++;
				}
				
//				Build the string for the reply
//				$mention_role_name_queue 								= "**$mention_id:** ";
				//$mention_role_id_queue 								= "**<@$mention_id>:**\n";
				foreach ($target_guildmember_roles_ids as $mention_role){
//					$mention_role_name_queue 							= "$mention_role_name_queue$mention_role, ";
					$mention_role_id_queue 								= "$mention_role_id_queue<@&$mention_role> ";
				}
//				$mention_role_name_queue 								= substr($mention_role_name_queue, 0, -2); 		//Get rid of the extra ", " at the end
				$mention_role_id_queue 									= substr($mention_role_id_queue, 0, -1); 		//Get rid of the extra ", " at the end 
//				$mention_role_name_queue_full 							= $mention_role_name_queue_full . PHP_EOL . $mention_role_name_queue;
				$mention_role_id_queue_full 							= PHP_EOL . $mention_role_id_queue;
			
//				Check if anyone had their roles changed
//				if ($mention_role_name_queue_default != $mention_role_name_queue){
				if ($mention_role_name_queue_default != $mention_role_id_queue){
//					Send the message
					if($react) $message->react("👍");
					//$message->reply($mention_role_name_queue_full . PHP_EOL);
//					Build the embed
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
//						->setTitle("Roles")																		// Set a title
						->setColor("a7c5fd")																	// Set a color (the thing on the left side)
						->setDescription("$author_guild_name")												// Set a description (below title, above fields)
//						->addField("Roles", 	"$mention_role_name_queue_full")								// New line after this
						->addField("Roles", 	"$mention_role_id_queue_full", true)							// New line after this
						
						->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
//						->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
						->setAuthor("$mention_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
//					Send the message
//					We do not need another promise here, so we call done, because we want to consume the promise
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo $error.PHP_EOL; //Echo any errors
					});
					return true; //No more processing
				}else{
					if($react) $message->react("👎");
					$message->reply("Nobody in the guild was mentioned!");
					return true;  //No more processing
				}
			}
			//Foreach method didn't return, so nobody was mentioned
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		
		//ymdhis cooldown time
		$avatar_limit['year']	= 0;
		$avatar_limit['month']	= 0;
		$avatar_limit['day']	= 0;
		$avatar_limit['hour']	= 0;
		$avatar_limit['min']	= 10;
		$avatar_limit['sec']	= 0;
		$avatar_limit_seconds = TimeArrayToSeconds($avatar_limit);																		//echo "TimeArrayToSeconds: " . $avatar_limit_seconds . PHP_EOL;
		if ($message_content_lower == $command_symbol . 'avatar'){ //;avatar
			echo "GETTING AVATAR FOR AUTHOR" . PHP_EOL;
//			Check Cooldown Timer
			$cooldown = CheckCooldown($author_folder, "avatar_time.php", $avatar_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
//				Build the embed
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
//					->setTitle("Avatar")																	// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//					->setDescription("$author_guild_name")												// Set a description (below title, above fields)
//					->addField("Total Given", 		"$vanity_give_count")									// New line after this
					
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
					->setImage("$author_avatar")             												// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
				
//				Send the message
//				We do not need another promise here, so we call done, because we want to consume the promise
				$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
//				Set Cooldown
				SetCooldown($author_folder, "avatar_time.php");
				return true;
			}else{
//				Reply with remaining time
				$waittime = $avatar_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using this command again.");
				return true;
			}
		}
		
		if (substr($message_content_lower, 0, 8) == $command_symbol . 'avatar '){//;avatar @
			echo "GETTING AVATAR FOR MENTIONED" . PHP_EOL;
//			Check Cooldown Timer
			$cooldown = CheckCooldown($author_folder, "avatar_time.php", $avatar_limit);
			if ( ($cooldown[0] == true) || ($bypass) ){
				$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
				if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "unmute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
				foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//					id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 								= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 										= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 										= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					$mention_username 									= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
					
					$mention_discriminator 								= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
					$mention_check 										= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID

//					Get the avatar URL of the mentioned user
                    $target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_user							= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
					$mention_avatar 									= "{$target_guildmember_user->getAvatarURL()}";
					
//					Build the embed
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
//					->setTitle("Avatar")																	// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//					->setDescription("$author_guild_name")												// Set a description (below title, above fields)
//					->addField("Total Given", 		"$vanity_give_count")									// New line after this
						
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
					->setImage("$mention_avatar")             												// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					->setAuthor("$mention_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
					
//					Send the message
//					We do not need another promise here, so we call done, because we want to consume the promise
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo $error.PHP_EOL; //Echo any errors
					});
//					Set Cooldown
					SetCooldown($author_folder, "avatar_time.php");
					return true;					
				}
				//Foreach method didn't return, so nobody was mentioned
				$author_channel->send("<@$author_id>, you need to mention someone!");
				return true;
			}else{
//				Reply with remaining time
				$waittime = $avatar_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using this command again.");
				return true;
			}
		}
		
		if ($suggestion_pending_channel)
		if (substr($message_content_lower, 0, 12) == $command_symbol . 'suggestion '){ //;suggestion
			$filter = "$command_symbol" . "suggestion ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
			$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
			if ( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter text for your suggestion");
			//Post embedded suggestion to suggestion_pending_channel
			//React with thumbsup and thumbsdown
		}
		
		if ($suggestion_approved_channel)
		if ($creator || $owner || $mod || $admin || $dev)
		if (substr($message_content_lower, 0, 12) == $command_symbol . 'suggestion approve '){ //;suggestion
			$filter = "$command_symbol" . "suggestion approve ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
			$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
			if( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter text for your suggestion");
			if(is_numeric($value)){
				//Get message id of index
				//Return if resolved message content is an empty string or null
				//Repost embedded suggestion to suggestion_approved_channel
				//React with thumbsup and thumbsdown
			}else return $message->reply("Invalid input! Please enter a valid message ID");
		}
		
		
		
		/*
		*********************
		*********************
		Mod/Admin command functions
		*********************
		*********************
		*/
		
		if ($creator || $owner || $mod || $admin || $dev)
		if (substr($message_content_lower, 0, 6) == $command_symbol . 'kick '){ //;kick //TODO: Check $reason
			echo "KICK" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "unmute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				
				if ($author_id != $mention_id){ //Don't let anyone kick themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_role_collection 				= $target_guildmember->roles;					//This is the Role object for the GuildMember
					
//  				Get the avatar URL of the mentioned user
//					$target_guildmember_user							= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
//					$mention_avatar 									= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);
					
//  				Populate arrays of the info we need
//  				$target_guildmember_roles_names 					= array();
					$x=0;
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzg = false;
					$target_guildmember_roles_ids = array();
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_18_id)		$target_adult 		= true;							//Author has the 18+ role
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_verified_id)	$target_verified 	= true;							//Author has the verified role
							if ($role->id == $role_bot_id)		$target_bot 		= true;							//Author has the bot role
							if ($role->id == $role_vzgbot_id)	$target_vzg 		= true;							//Author is this bot
							if ($role->id == $role_muted_id)	$target_muted 		= true;							//Author is this bot
						}
						$x++;
					}
					if(!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg){
						if ($mention_check == $creator_check) return true; //Don't kick the creator
						//Build the string to log
						$filter = "$command_symbol" . "kick <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Kicked:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Kick the user
						$target_guildmember->kick($reason)->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
						if($react) $message->react("🥾"); //Boot
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
//							->setTitle("Commands")																	// Set a title
							->setColor("a7c5fd")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
//							->addField("⠀", "$reason")																// New line after this
							
//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
						return true;
					}else{//Target is not allowed to be kicked
						$author_channel->send("<@$mention_id> cannot be kicked because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't kick yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		
		if ( ($role_muted_id != "") || ($role_muted_id != NULL) )
		if ($creator || $owner || $mod || $admin || $dev)
		if (substr($message_content_lower, 0, 6) == $command_symbol . 'mute '){ //;mute
			echo "MUTE" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users;
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "mute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				
				if ($author_id != $mention_id){ //Don't let anyone mute themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_role_collection 				= $target_guildmember->roles;					//This is the Role object for the GuildMember
					
//  				Populate arrays of the info we need
//	    			$target_guildmember_roles_names 					= array();
					$x=0;
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzg = false;
					$target_guildmember_roles_ids = array();
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_vzgbot_id)	$target_vzg 		= true;							//Author is this bot
						}
						$x++;
					}
					if(!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg){
						if ($mention_check == $creator_check) return true; //Don't mute the creator
						//Build the string to log
						$filter = "$command_symbol" . "mute <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Muted:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Mute the user and remove the verified role
						$target_guildmember->addRole($role_muted_id);
						if ( ($role_verified_id != "") || ($role_verified_id != NULL) )
							$target_guildmember->removeRole($role_verified_id);
						if($react) $message->react("🤐");
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
//							->setTitle("Commands")																	// Set a title
							->setColor("a7c5fd")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
//							->addField("⠀", "$reason")																// New line after this
							
//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  									// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
						return true;
					}else{//Target is not allowed to be muted
						$author_channel->send("<@$mention_id> cannot be muted because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't mute yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		
		if ( ($role_muted_id != "") || ($role_muted_id != NULL) )
		if ($creator || $owner || $mod || $admin || $dev)
		if (substr($message_content_lower, 0, 8) == $command_symbol . 'unmute '){ //;unmute
			echo "UNMUTE" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "unmute ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				
				if ($author_id != $mention_id){ //Don't let anyone mute themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id);
					$target_guildmember_role_collection 				= $target_guildmember->roles;

//					Get the roles of the mentioned user
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzg = false;
//					Populate arrays of the info we need
					$target_guildmember_roles_ids = array();
					$x=0;
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_vzgbot_id)	$target_vzg 		= true;							//Author is this bot
						}
						$x++;
					}
					if(!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg){
						if ($mention_check == $creator_check) return true; //Don't mute the creator
						//Build the string to log
						$filter = "$command_symbol" . "unmute <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Unmuted:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Unmute the user and readd the verified role
						$target_guildmember->removeRole($role_muted_id);
						if ( ($role_verified_id != "") || ($role_verified_id != NULL) )
							$target_guildmember->addRole($role_verified_id);
						if($react) $message->react("😩");
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
//							->setTitle("Commands")																	// Set a title
							->setColor("a7c5fd")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
//							->addField("⠀", "$reason")																// New line after this
							
//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
						return true;
					}else{//Target is not allowed to be unmuted
						$author_channel->send("<@$mention_id> cannot be unmuted because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't mute yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		
		if ($admin || $owner || $creator)
		if (substr($message_content_lower, 0, 5) == $command_symbol . 'ban '){ //;ban
			echo "BAN" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "ban ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value);
				$value = str_replace(">", "", $value); echo "value: " . $value . PHP_EOL;
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			foreach ( $mentions_arr as $mention_param ){
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 				//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator;
				
				if ($author_id != $mention_id){ //Don't let anyone ban themselves
					//Get the roles of the mentioned user
					$target_guildmember 								= $message->guild->members->get($mention_id); 	//This is a GuildMember object
					$target_guildmember_role_collection 				= $target_guildmember->roles;					//This is the Role object for the GuildMember
					
//  				Get the avatar URL of the mentioned user
//					$target_guildmember_user							= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
//					$mention_avatar 									= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;				//echo "target_guildmember_role_collection: " . (count($target_guildmember_role_collection)-1);

//  				Populate arrays of the info we need
//  				$target_guildmember_roles_names 					= array();
					$x=0;
					$target_dev = false;
					$target_owner = false;
					$target_admin = false;
					$target_mod = false;
					$target_vzg = false;
					$target_guildmember_roles_ids = array();
					foreach ($target_guildmember_role_collection as $role){
						if ($x!=0){ //0 is @everyone so skip it
							$target_guildmember_roles_ids[] 						= $role->id; 											//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
							if ($role->id == $role_dev_id)    	$target_dev 		= true;							//Author has the dev role
							if ($role->id == $role_owner_id)    $target_owner	 	= true;							//Author has the owner role
							if ($role->id == $role_admin_id)	$target_admin 		= true;							//Author has the admin role
							if ($role->id == $role_mod_id)		$target_mod 		= true;							//Author has the mod role
							if ($role->id == $role_vzgbot_id)	$target_vzg 		= true;							//Author is this bot
						}
						$x++;
					}
					if(!$target_dev && !$target_owner && !$target_admin && !$target_mod && !$target_vzg){
						if ($mention_check == $creator_check) return true; //Don't ban the creator
						//Build the string to log
						$filter = "$command_symbol" . "ban <@!$mention_id>";
						$warndate = date("m/d/Y");
						$reason = "**🥾Banned:** <@$mention_id>
						**🗓️Date:** $warndate
						**📝Reason:** " . str_replace($filter, "", $message_content);
						//Ban the user and clear 1 days worth of messages
						$target_guildmember->ban("1", $reason)->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
						//Build the embed message
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
//							->setTitle("Commands")																	// Set a title
							->setColor("a7c5fd")																	// Set a color (the thing on the left side)
							->setDescription("$reason")																// Set a description (below title, above fields)
//							->addField("⠀", "$reason")																// New line after this
							
//							->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
//						Send the message
						if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
						if($react) $message->react("🔨"); //Hammer
						return true; //No more processing, we only want to process the first person mentioned
					}else{//Target is not allowed to be banned
						$author_channel->send("<@$mention_id> cannot be banned because of their roles!");
						return true;
					}
				}else{
					if($react) $message->react("👎");
					$author_channel->send("<@$author_id>, you can't ban yourself!");
					return true;
				}
			} //foreach method didn't return, so nobody was mentioned
			if($react) $message->react("👎");
			$author_channel->send("<@$author_id>, you need to mention someone!");
			return true;
		}
		
		/*
		*********************
		*********************
		Vanity command functions
		*********************
		*********************
		*/
		if (!$vzgbot)
		if ($vanity){
			//ymdhis cooldown time
			$vanity_limit['year'] = 0;
			$vanity_limit['month'] = 0;
			$vanity_limit['day'] = 0;
			$vanity_limit['hour'] = 0;
			$vanity_limit['min'] = 10;
			$vanity_limit['sec'] = 0;
			$vanity_limit_seconds = TimeArrayToSeconds($vanity_limit);
//			Load author give statistics
			if(!CheckFile($author_folder, "vanity_give_count.php"))	$vanity_give_count	= 0;													
			else 													$vanity_give_count	= VarLoad($author_folder, "vanity_give_count.php");		
			if(!CheckFile($author_folder, "hugger_count.php"))		$hugger_count		= 0;													
			else 													$hugger_count 		= VarLoad($author_folder, "hugger_count.php");				
			if(!CheckFile($author_folder, "kisser_count.php"))		$kisser_count		= 0;													
			else 													$kisser_count 		= VarLoad($author_folder, "kisser_count.php");				
			if(!CheckFile($author_folder, "nuzzler_count.php"))		$nuzzler_count		= 0;													
			else 													$nuzzler_count		= VarLoad($author_folder, "nuzzler_count.php");			
			if(!CheckFile($author_folder, "booper_count.php"))		$booper_count		= 0;													
			else 													$booper_count		= VarLoad($author_folder, "booper_count.php");			
			if(!CheckFile($author_folder, "baper_count.php"))		$baper_count		= 0;													
			else 													$baper_count		= VarLoad($author_folder, "baper_count.php");			

//			Load author get statistics
			if(!CheckFile($author_folder, "vanity_get_count.php"))	$vanity_get_count	= 0;													
			else 													$vanity_get_count 	= VarLoad($author_folder, "vanity_get_count.php");		
			if(!CheckFile($author_folder, "hugged_count.php"))		$hugged_count		= 0;													
			else 													$hugged_count 		= VarLoad($author_folder, "hugged_count.php");				
			if(!CheckFile($author_folder, "kissed_count.php"))		$kissed_count		= 0;													
			else 													$kissed_count 		= VarLoad($author_folder, "kissed_count.php");				
			if(!CheckFile($author_folder, "nuzzled_count.php"))		$nuzzled_count		= 0;													
			else 													$nuzzled_count		= VarLoad($author_folder, "nuzzled_count.php");				
			if(!CheckFile($author_folder, "booped_count.php"))		$booped_count		= 0;													
			else 													$booped_count		= VarLoad($author_folder, "booped_count.php");
			if(!CheckFile($author_folder, "baped_count.php"))		$baped_count		= 0;													
			else 													$baped_count		= VarLoad($author_folder, "baped_count.php");				
			
			if ( (substr($message_content_lower, 0, 5) == $command_symbol . 'hug ') || (substr($message_content_lower, 0, 9) == $command_symbol . 'snuggle ') ){ //;hug ;snuggle
				echo "HUG/SNUGGLE" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Get an array of people mentioned
					$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
					foreach ( $mentions_arr as $mention_param ){
						$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
						$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
						$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
						
						if ($author_id != $mention_id){
							$hug_messages								= array();
							$hug_messages[]								= "<@$author_id> has given <@$mention_id> a hug! How sweet!";
							$hug_messages[]								= "<@$author_id> saw that <@$mention_id> needed attention, so <@$author_id> gave them a hug!";
							$hug_messages[]								= "<@$author_id> gave <@$mention_id> a hug! Isn't this adorable?";
							$index_selection							= GetRandomArrayIndex($hug_messages);

							//Send the message
							$author_channel->send($hug_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$hugger_count++;
							VarSave($author_folder, "hugger_count.php", $hugger_count);
							//Load target get statistics
							if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
							else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
							if(!CheckFile($guild_folder."/".$mention_id, "hugged_count.php"))		$hugged_count		= 0;
							else 																	$hugged_count 		= VarLoad($guild_folder."/".$mention_id, "hugged_count.php");
							//Increment get stat counter of target
							$vanity_get_count++;
							VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
							$hugged_count++;
							VarSave($guild_folder."/".$mention_id, "hugged_count.php", $hugged_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}else{
							$self_hug_messages							= array();
							$self_hug_messages[]						= "<@$author_id> hugs themself. What a wierdo!";
							$index_selection							= GetRandomArrayIndex($self_hug_messages);
							//Send the message
							$author_channel->send($self_hug_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$hugger_count++;
							VarSave($author_folder, "hugger_count.php", $hugger_count);
							//Increment get stat counter of author
							$vanity_get_count++;
							VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
							$hugged_count++;
							VarSave($author_folder, "hugged_count.php", $hugged_count);
							//Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}
					}
					//foreach method didn't return, so nobody was mentioned
					$author_channel->send("<@$author_id>, you need to mention someone!");
					return true;
				}else{
//				Reply with remaining time
				$waittime = $vanity_limit_seconds - $cooldown[1];
				$formattime = FormatTime($waittime);
				$message->reply("You must wait $formattime before using vanity commands again.");
				return true;
				}
			}
			
			if ( (substr($message_content_lower, 0, 6) == $command_symbol . 'kiss ') || (substr($message_content_lower, 0, 8)) == $command_symbol . 'smooch '){ //;kiss ;smooch
				echo "KISS" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Get an array of people mentioned
					$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
					foreach ( $mentions_arr as $mention_param ){
						$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
						$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
						$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
						
						if ($author_id != $mention_id){
							$kiss_messages								= array();
							$kiss_messages[]							= "<@$author_id> put their nose to <@$mention_id>’s for a good old smooch! Now that’s cute!";
							$kiss_messages[]							= "<@$mention_id> was surprised when <@$author_id> leaned in and gave them a kiss! Hehe!";
							$kiss_messages[]							= "<@$author_id> has given <@$mention_id> the sweetest kiss on the cheek! Yay!";
							$kiss_messages[]							= "<@$author_id> gives <@$mention_id> a kiss on the snoot.";
							$kiss_messages[]							= "<@$author_id> rubs their snoot on <@$mention_id>, how sweet!";
							$index_selection							= GetRandomArrayIndex($kiss_messages);						//echo "random kiss_message: " . $kiss_messages[$index_selection];
//							Send the message
							$author_channel->send($kiss_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$kisser_count++;
							VarSave($author_folder, "kisser_count.php", $kisser_count);
							//Load target get statistics
							if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
							else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
							if(!CheckFile($guild_folder."/".$mention_id, "kissed_count.php"))		$kissed_count		= 0;
							else 																	$kissed_count 		= VarLoad($guild_folder."/".$mention_id, "kissed_count.php");
							//Increment get stat counter of target
							$vanity_get_count++;
							VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
							$kissed_count++;
							VarSave($guild_folder."/".$mention_id, "kissed_count.php", $kissed_count);\
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}else{
							$self_kiss_messages							= array();
							$self_kiss_messages[]						= "<@$author_id> tried to kiss themselves in the mirror. How silly!";
							$index_selection							= GetRandomArrayIndex($self_kiss_messages);
							//Send the message
							$author_channel->send($self_kiss_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$kisser_count++;
							VarSave($author_folder, "kisser_count.php", $kisser_count);
							//Increment get stat counter of author
							$vanity_get_count++;
							VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
							$kissed_count++;
							VarSave($author_folder, "kissed_count.php", $kissed_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}
					}
					//foreach method didn't return, so nobody was mentioned
					$author_channel->send("<@$author_id>, you need to mention someone!");
					return true;
				}else{
//					Reply with remaining time
					$waittime = $vanity_limit_seconds - $cooldown[1];
					$formattime = FormatTime($waittime);
					$message->reply("You must wait $formattime before using vanity commands again.");
					return true;
				}
			}
			
			if (substr($message_content_lower, 0, 8) == $command_symbol . 'nuzzle ' ){ //;nuzzle @
				echo "NUZZLE" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Get an array of people mentioned
					$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
					foreach ( $mentions_arr as $mention_param ){
						$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
						$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
						$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
						
						if ($author_id != $mention_id){
							$nuzzle_messages							= array();
							$nuzzle_messages[]							= "<@$author_id> nuzzled into <@$mention_id>’s neck! Sweethearts~ :blue_heart:";
							$nuzzle_messages[]							= "<@$mention_id> was caught off guard when <@$author_id> nuzzled into their chest! How cute!";
							$nuzzle_messages[]							= "<@$author_id> wanted to show <@$mention_id> some more affection, so they nuzzled into <@$mention_id>’s fluff!";
							$nuzzle_messages[]							= "<@$author_id> rubs their snoot softly against <@$mention_id>, look at those cuties!";
							$nuzzle_messages[]							= "<@$author_id> takes their snoot and nuzzles <@$mention_id> cutely.";
							$index_selection							= GetRandomArrayIndex($nuzzle_messages);
//							echo "random nuzzle_messages: " . $nuzzle_messages[$index_selection];
//							Send the message
							$author_channel->send($nuzzle_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$nuzzler_count++;
							VarSave($author_folder, "nuzzler_count.php", $nuzzler_count);
							//Load target get statistics
							if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
							else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
							if(!CheckFile($guild_folder."/".$mention_id, "nuzzled_count.php"))		$nuzzled_count		= 0;
							else 																	$nuzzled_count 		= VarLoad($guild_folder."/".$mention_id, "nuzzled_count.php");
							//Increment get stat counter of target
							$vanity_get_count++;
							VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
							$nuzzled_count++;
							VarSave($guild_folder."/".$mention_id, "nuzzled_count.php", $nuzzled_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}else{
							$self_nuzzle_messages						= array();
							$self_nuzzle_messages[]						= "<@$author_id> curled into a ball in an attempt to nuzzle themselves.";
							$index_selection							= GetRandomArrayIndex($self_nuzzle_messages);
//							Send the mssage
							$author_channel->send($self_nuzzle_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$nuzzler_count++;
							VarSave($author_folder, "nuzzler_count.php", $nuzzler_count);
							//Increment get stat counter of author
							$vanity_get_count++;
							VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
							$nuzzled_count++;
							VarSave($author_folder, "nuzzled_count.php", $nuzzled_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}
					}
					//Foreach method didn't return, so nobody was mentioned
					$author_channel->send("<@$author_id>, you need to mention someone!");
					return true;
				}else{
//					Reply with remaining time
					$waittime = $vanity_limit_seconds - $cooldown[1];
					$formattime = FormatTime($waittime);
					$message->reply("You must wait $formattime before using vanity commands again.");
					return true;
				}
			}
			
			if (substr($message_content_lower, 0, 6) == $command_symbol . 'boop ' ){ //;boop @
				echo "BOOP" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Get an array of people mentioned
					$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
					foreach ( $mentions_arr as $mention_param ){
						$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
						$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
						$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
						
						if ($author_id != $mention_id){
							$boop_messages								= array();
							$boop_messages[]							= "<@$author_id> slowly and strategically booped the snoot of <@$mention_id>.";
							$boop_messages[]							= "With a playful smile, <@$author_id> booped <@$mention_id>'s snoot.";
							$index_selection							= GetRandomArrayIndex($boop_messages);
//							echo "random boop_messages: " . $boop_messages[$index_selection];
//							Send the message
							$author_channel->send($boop_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$booper_count++;
							VarSave($author_folder, "booper_count.php", $booper_count);
							//Load target get statistics
							if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
							else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
							if(!CheckFile($guild_folder."/".$mention_id, "booped_count.php"))		$booped_count		= 0;
							else 																	$booped_count 		= VarLoad($guild_folder."/".$mention_id, "booped_count.php");
							//Increment get stat counter of target
							$vanity_get_count++;
							VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
							$booped_count++;
							VarSave($guild_folder."/".$mention_id, "booped_count.php", $booped_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}else{
							$self_boop_messages							= array();
							$self_boop_messages[]						= "<@$author_id> placed a paw on their own nose. How silly!";
							$index_selection							= GetRandomArrayIndex($self_boop_messages);
//							Send the mssage
							$author_channel->send($self_boop_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$booper_count++;
							VarSave($author_folder, "booper_count.php", $booper_count);
							//Increment get stat counter of author
							$vanity_get_count++;
							VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
							$booped_count++;
							VarSave($author_folder, "booped_count.php", $booped_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing
						}
					}
					//Foreach method didn't return, so nobody was mentioned
					$author_channel->send("<@$author_id>, you need to mention someone!");
					return true;
				}else{
//					Reply with remaining time
					$waittime = $vanity_limit_seconds - $cooldown[1];
					$formattime = FormatTime($waittime);
					$message->reply("You must wait $formattime before using vanity commands again.");
					return true;
				}
			}
			
			if (substr($message_content_lower, 0, 5) == $command_symbol . 'bap ' ){ //;bap @
				echo "BAP" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vanity_time.php", $vanity_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Get an array of people mentioned
					$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
					foreach ( $mentions_arr as $mention_param ){
						$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
						$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
						$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
						
						if ($author_id != $mention_id){
							$bap_messages								= array();
							$bap_messages[]								= "<@$mention_id> was hit on the snoot by <@$author_id>!";
							$bap_messages[]								= "<@$author_id> glared at <@$mention_id>, giving them a bap on the snoot!";
							$bap_messages[]								= "Snoot of <@$mention_id> was attacked by <@$author_id>!";
							$index_selection							= GetRandomArrayIndex($bap_messages);
//							echo "random bap_messages: " . $bap_messages[$index_selection];
//							Send the message
							$author_channel->send($bap_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$baper_count++;
							VarSave($author_folder, "baper_count.php", $baper_count);
							//Load target get statistics
							if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
							else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
							if(!CheckFile($guild_folder."/".$mention_id, "baped_count.php"))		$baped_count		= 0;
							else 																	$baped_count 		= VarLoad($guild_folder."/".$mention_id, "baped_count.php");
							//Increment get stat counter of target
							$vanity_get_count++;
							VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
							$baped_count++;
							VarSave($guild_folder."/".$mention_id, "baped_count.php", $baped_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}else{
							$self_bap_messages							= array();
							$self_bap_messages[]						= "<@$author_id> placed a paw on their own nose. How silly!";
							$index_selection							= GetRandomArrayIndex($self_bap_messages);
//							Send the mssage
							$author_channel->send($self_bap_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$baper_count++;
							VarSave($author_folder, "baper_count.php", $baper_count);
							//Increment get stat counter of author
							$vanity_get_count++;
							VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
							$baped_count++;
							VarSave($author_folder, "baped_count.php", $baped_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing
						}
					}
					//Foreach method didn't return, so nobody was mentioned
					$author_channel->send("<@$author_id>, you need to mention someone!");
					return true;
				}else{
//					Reply with remaining time
					$waittime = $vanity_limit_seconds - $cooldown[1];
					$formattime = FormatTime($waittime);
					$message->reply("You must wait $formattime before using vanity commands again.");
					return true;
				}
			}
			
			//TODO: Spin The Bottle
			
			//ymdhis cooldown time
			$vstats_limit['year'] = 0;
			$vstats_limit['month'] = 0;
			$vstats_limit['day'] = 0;
			$vstats_limit['hour'] = 0;
			$vstats_limit['min'] = 30;
			$vstats_limit['sec'] = 0;
			$vstats_limit_seconds = TimeArrayToSeconds($vstats_limit);
			
			if ($message_content_lower == $command_symbol . 'vstats' ){ //;vstats //Give the author their vanity stats as an embedded message
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vstats_limit.php", $vstats_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Build the embed
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("Vanity Stats")																// Set a title
						->setColor("a7c5fd")																	// Set a color (the thing on the left side)
						->setDescription("$author_guild_name")												// Set a description (below title, above fields)
						->addField("Total Given", 		"$vanity_give_count")									// New line after this
						->addField("Hugs", 				"$hugger_count", true)
						->addField("Kisses", 			"$kisser_count", true)
						->addField("Nuzzles", 			"$nuzzler_count", true)
						->addField("Boops", 			"$booper_count", true)
						->addField("Baps", 				"$baper_count", true)
						->addField("⠀", 				"⠀", true)												// Invisible unicode for separator
						->addField("Total Received", 	"$vanity_get_count")									// New line after this
						->addField("Hugs", 				"$hugged_count", true)
						->addField("Kisses", 			"$kissed_count", true)
						->addField("Nuzzles", 			"$nuzzled_count", true)
						->addField("Boops", 			"$booped_count", true)
						->addField("Baps", 				"$baped_count", true)
						
						->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//						->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
						->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
					
//					Send the message
//					We do not need another promise here, so we call done, because we want to consume the promise
					if($react) $message->react("👍");
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo $error.PHP_EOL; //Echo any errors
					});
//					Set Cooldown
					SetCooldown($author_folder, "vstats_limit.php");
					return true;
				}else{
//					Reply with remaining time
					$waittime = ($vstats_limit_seconds - $cooldown[1]);
					$formattime = FormatTime($waittime);
					if($react) $message->react("👎");
					$message->reply("You must wait $formattime before using vstats on yourself again.");
					return true;
				}
			}
			
			if (substr($message_content_lower, 0, 8) == $command_symbol . 'vstats ' ){ //;vstats @
				echo "GETTING VANITY STATS OF MENTIONED" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vstats_limit.php", $vstats_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
//					Get an array of people mentioned
					$mentions_arr 										= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object			
					foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//						id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
						$mention_param_encode 							= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
						$mention_json 									= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
						$mention_id 									= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
						$mention_username 								= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
						$mention_discriminator 							= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
						$mention_check 									= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
						
//						Get the avatar URL
						$target_guildmember 							= $message->guild->members->get($mention_id); 	//This is a GuildMember object
						$target_guildmember_user						= $target_guildmember->user;									//echo "member_class: " . get_class($target_guildmember_user) . PHP_EOL;
						$mention_avatar 								= "{$target_guildmember_user->getAvatarURL()}";					//echo "mention_avatar: " . $mention_avatar . PHP_EOL;
						
						
						//Load target get statistics
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$target_vanity_get_count	= 0;
						else 																	$target_vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "vanity_give_count.php"))	$target_vanity_give_count	= 0;
						else 																	$target_vanity_give_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_give_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "hugged_count.php"))		$target_hugged_count		= 0;
						else 																	$target_hugged_count 		= VarLoad($guild_folder."/".$mention_id, "hugged_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "hugger_count.php"))		$target_hugger_count		= 0;
						else 																	$target_hugger_count 		= VarLoad($guild_folder."/".$mention_id, "hugger_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "kissed_count.php"))		$target_kissed_count		= 0;
						else 																	$target_kissed_count 		= VarLoad($guild_folder."/".$mention_id, "kissed_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "kisser_count.php"))		$target_kisser_count		= 0;
						else 																	$target_kisser_count 		= VarLoad($guild_folder."/".$mention_id, "kisser_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "nuzzled_count.php"))		$target_nuzzled_count		= 0;
						else 																	$target_nuzzled_count 		= VarLoad($guild_folder."/".$mention_id, "nuzzled_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "nuzzler_count.php"))		$target_nuzzler_count		= 0;
						else 																	$target_nuzzler_count 		= VarLoad($guild_folder."/".$mention_id, "nuzzler_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "booped_count.php"))		$target_booped_count		= 0;
						else 																	$target_booped_count 		= VarLoad($guild_folder."/".$mention_id, "booped_count.php");
						if(!CheckFile($guild_folder."/".$mention_id, "booper_count.php"))		$target_booper_count		= 0;
						else 																	$target_booper_count 		= VarLoad($guild_folder."/".$mention_id, "booper_count.php");
						
						//Build the embed
						$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
						$embed
							->setTitle("Vanity Stats")																// Set a title
							->setColor("a7c5fd")																	// Set a color (the thing on the left side)
							->setDescription("$author_guild_name")												// Set a description (below title, above fields)
							->addField("Total Given", 		"$target_vanity_give_count")							// New line after this
							->addField("Hugs", 				"$target_hugger_count", true)
							->addField("Kisses", 			"$target_kisser_count", true)
							->addField("Nuzzles", 			"$target_nuzzler_count", true)
							->addField("Boops", 			"$target_booper_count", true)
							->addField("⠀", 				"⠀", true)												// Invisible unicode for separator
							->addField("Total Received", 	"$target_vanity_get_count")								// New line after this
							->addField("Hugs", 				"$target_hugged_count", true)
							->addField("Kisses", 			"$target_kissed_count", true)
							->addField("Nuzzles", 			"$target_nuzzled_count", true)
							->addField("Boops", 			"$target_booped_count", true)
							
							->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
//							->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             		// Set an image (below everything except footer)
							->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
							->setAuthor("$mention_check", "$author_guild_avatar")  // Set an author with icon
							->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
							->setURL("");                             												// Set the URL
						
//						Send the message
//						We do not need another promise here, so we call done, because we want to consume the promise
						if($react) $message->react("👍");
						$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
							echo $error.PHP_EOL; //Echo any errors
						});
//						Set Cooldown
						SetCooldown($author_folder, "vstats_limit.php");
						return true; //No more processing, we only want to process the first person mentioned
					}
					//Foreach method didn't return, so nobody was mentioned
					$author_channel->send("<@$author_id>, you need to mention someone!");
					return true;
				}else{
//					Reply with remaining time
					$waittime = ($vstats_limit_seconds - $cooldown[1]);
					$formattime = FormatTime($waittime);
					if($react) $message->react("👎");
					$message->reply("You must wait $formattime before using vstats on yourself again.");
					return true;
				}
			}
			
		} //End of vanity commands
		
		/*
		*********************
		*********************
		Role picker functions
		*********************
		*********************
		*/
		
		//TODO? (This is already done with messageReactionAdd)
		
		/*
		*********************
		*********************
		Restricted command functions
		*********************
		*********************
		*/
		
		if($creator || $owner || $dev || $admin || $mod) //Only allow these roles to use this
		if (substr($message_content_lower, 0, 7) == $command_symbol . 'whois '){ //;whois
			echo "WHOIS" . PHP_EOL;			
			$filter = "$command_symbol" . "whois ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				$mention_member				= $author_guild->members->get($value);
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
				$mention_user				= $mention_member->user;
				
				$mention_id					= $mention_member->id;
				$mention_check				= $mention_user->tag;
				$mention_nickname			= $mention_member->displayName;
				$mention_avatar 			= $mention_user->getAvatarURL();
				
				$mention_joined				= $mention_member->joinedAt;
				$mention_joinedTimestamp	= $mention_member->joinedTimestamp;
				$mention_joinedDate			= date("D M j H:i:s Y", $mention_joinedTimestamp);
				$mention_joinedDateTime		= new DateTime('@' . $mention_joinedTimestamp);
				
				$mention_created			= $mention_user->createdAt;
				$mention_createdTimestamp	= $mention_user->createdTimestamp;
				$mention_createdDate		= date("D M j H:i:s Y", $mention_createdTimestamp);
				$mention_createdDateTime	= new DateTime('@' . $mention_createdTimestamp);
				
				$mention_joinedAge = $mention_joinedDateTime->diff($now)->days . " days";
				$mention_createdAge = $mention_createdDateTime->diff($now)->days . " days";
				
				//Load history
				$mention_folder = "users/$mention_id";
				CheckDir($mention_folder);
				$mention_nicknames_array = array_reverse(VarLoad($mention_folder, "nicknames.php"));
				$x=0;
				foreach ($mention_nicknames_array as $nickname){
					if ($x<5)
						$mention_nicknames = $mention_nicknames . $nickname . "\n";
					$x++;
				}
				if ($mention_nicknames == NULL) $mention_nicknames = "No nicknames tracked";
				
				$mention_tags_array = array_reverse(VarLoad($mention_folder, "tags.php"));
				$x=0;
				foreach ($mention_tags_array as $tag){
					if ($x<5)
						$mention_tags = $mention_tags . $tag . "\n";
					$x++;
				}
				if ($mention_tags == NULL) $mention_tags = "No tags tracked";
				 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
					->setTitle("$mention_check ($mention_nickname)")																// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//					->setDescription("$author_guild_name")									// Set a description (below title, above fields)
					->addField("ID", "$mention_id", true)
					->addField("Avatar", "[Link]($mention_avatar)", true)
					->addField("Account Created", "$mention_createdDate", true)
					->addField("Account Age", "$mention_createdAge", true)
					->addField("Joined Server", "$mention_joinedDate", true)
					->addField("Server Age", "$mention_joinedAge", true)
					->addField("Tag history (last 5)", "`$mention_tags`")
					->addField("Nickname history (last 5)", "`$mention_nicknames`")

					->setThumbnail("$mention_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
//					->setImage("$image_path")             													// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
				$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
			}else $message->reply("Invalid input! Please enter an ID or @mention the user");
			return true;
		}
		
		if ($creator) //Only allow these roles to use this
		if ($message_content_lower == $command_symbol . 'genimage'){
			include "imagecreate_include.php"; //Generates $img_output_path
			$image_path = "http://www.valzargaming.com/discord%20-%20palace/" . $img_output_path;
			//echo "image_path: " . $image_path . PHP_EOL;
//			Build the embed message
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
//				->setTitle("$author_check")																// Set a title
				->setColor("a7c5fd")																	// Set a color (the thing on the left side)
				->setDescription("$author_guild_name")									// Set a description (below title, above fields)
//				->addField("⠀", "$documentation")														// New line after this
				
				->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				->setImage("$image_path")             													// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
//				Open a DM channel then send the rich embed message
			/*
			$author_user->createDM()->then(function($author_dmchannel) use ($message, $embed){	//Promise
				echo 'SEND GENIMAGE EMBED' . PHP_EOL;
				$author_dmchannel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
			});
			*/
			$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
				echo $error.PHP_EOL; //Echo any errors
			});
			return true;
		}
		
		if ($creator) //Only allow these roles to use this
		if ($message_content_lower == $command_symbol . 'processmessages'){	
//			$verify_channel																					//TextChannel				//echo "channel_messages class: " . get_class($verify_channel) . PHP_EOL;
//			$author_messages = $verify_channel->fetchMessages(); 											//Promise
//			echo "author_messages class: " . get_class($author_messages) . PHP_EOL; 						//Promise
			$verify_channel->fetchMessages()->then(function($message_collection) use ($verify_channel){	//Resolve the promise
//				$verify_channel and the new $message_collection can be used here
//				echo "message_collection class: " . get_class($message_collection) . PHP_EOL; 				//Collection messages
				foreach ($message_collection as $message){													//Model/Message				//echo "message_collection message class:" . get_class($message) . PHP_EOL;
//					DO STUFF HERE TO MESSAGES
				}
			});
			return true;
		}
		
		if ($creator) //Only allow these roles to use this
		if ($message_content_lower == $command_symbol . 'restart'){
			echo "RESTARTING BOT" . PHP_EOL;
			$restart_cmd = 'cmd /c "'. __DIR__  . '\run.bat"';
			//echo $restart_cmd . PHP_EOL;
			system($restart_cmd);
			//echo 'die' . PHP_EOL;
			//die;
		}
		
		if ( ($role_verified_id != "") || ($role_verified_id != NULL) )
		if ($creator || $owner || $dev || $admin || $mod) //Only allow these roles to use this
		if ( (substr($message_content_lower, 0, 3) == $command_symbol . 'v ') || (substr(($message_content), 0, 8) == $command_symbol . 'verify ') ){ //Verify ;v ;verify
			echo "GIVING VERIFIED ROLE TO MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			$mention_role_name_queue_default							= "<@$author_id> verified the following users:" . PHP_EOL;
			$mention_role_name_queue_full 								= $mention_role_name_queue_default;
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "v ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				$filter = "$command_symbol" . "verify ";
				$value = str_replace($filter, "", $value);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
//				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
//				$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
				
//				Get the roles of the mentioned user
				echo "mention_id: " . $mention_id . PHP_EOL;
				$target_guildmember 									= $message->guild->members->get($mention_id);
				$target_guildmember_role_collection 					= $target_guildmember->roles;									//echo "target_guildmember_role_collection: " . (count($author_guildmember_role_collection)-1);
				
//				Populate arrays of the info we need
				$target_verified										= false; //Default
				$x=0;
				foreach ($target_guildmember_role_collection as $role){
					if ($x!=0){ //0 is @everyone so skip it
						if ($role->id == $role_verified_id)
							$target_verified 							= true;
					}
					$x++;
				}
				
				if($target_verified == false){
//					Build the string for the reply
					$mention_role_name_queue 							= "**<@$mention_id>** ";
					$mention_role_name_queue_full 						= $mention_role_name_queue_full . PHP_EOL . $mention_role_name_queue;
//					Add the verified role to the member
					$target_guildmember->addRole($role_verified_id)->done(
						function (){
							//if ($general_channel) $general_channel->send('Welcome to the Palace, <@$mention_id>! Feel free to pick out some roles in #role-picker!');
						},
						function ($error) {
							throw $error;
						}
					);
					echo "Verify role added ($role_verified_id)" . PHP_EOL;
				}
			}
//			Send the message
			if ($mention_role_name_queue_default != $mention_role_name_queue_full){
				/*
				if($verify_channel){
					if($react) $message->react("👍");
					if($verify_channel)
						$verify_channel->send($mention_role_name_queue_full . PHP_EOL);
					return true;
				}
				*/
				if($react) $message->react("👍");
				if($author_channel)
					$author_channel->send($mention_role_name_queue_full . PHP_EOL);
				if($general_channel){
					$msg = "Welcome to the Palace, <@$mention_id>!";
					if ($rolepicker_channel_id != "" && $rolepicker_channel_id != NULL) $msg = $msg . " Feel free to pick out some roles in <#$rolepicker_channel_id>.";
					if($general_channel)$general_channel->send($msg);
				}
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody mentioned needs to be verified!" . PHP_EOL);
				return true;
			}	
		}
		
		if( ($getverified_channel_id != "") || ($getverified_channel_id != NULL)) //User was kicked (They have no roles anymore)
		if ($creator || $owner || $dev || $admin || $mod) //Only allow these roles to use this
		if ( ($message_content_lower == $command_symbol . 'cv') || ( $message_content_lower == $command_symbol . 'clearv') ){ //;clearv ;cv Clear all messages in the get-verified channel
			echo "CV" . PHP_EOL;
			$getverified_channel->bulkDelete(100);
			//Delete any messages that aren't cached
			$getverified_channel->fetchMessages()->then(function($message_collection) use ($getverified_channel){
				foreach ($message_collection as $message){
					$getverified_channel->message->delete();
				}
			});
			if($getverified_channel)$getverified_channel->send("Welcome to $author_guild_name! Please introduce yourself here and one of our staff members will verify you shortly. Be sure to include info about your fursona, your age, where you found us, and why you want to join us!");
			return true;
		}		
		
		if ($creator || $owner || $dev || $admin) //Only allow these roles to use this
		if ($message_content_lower == $command_symbol . 'clearall'){ //;clearall Clear as many messages in the author's channel at once as possible
			echo "CLEARALL" . PHP_EOL;
			$author_channel->bulkDelete(100);
			//Delete any messages that aren't cached
			$author_channel->fetchMessages()->then(function($message_collection) use ($author_channel){
				foreach ($message_collection as $message){
					$author_channel->message->delete();
				}
			});
			return true;
		};
		
		if ($creator || $owner || $dev || $admin || $mod) //Only allow these roles to use this
		if (substr($message_content_lower, 0, 7) == $command_symbol . 'watch '){ //;watch @
			echo "SETTING WATCH ON TARGETS MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if ($watch_channel)	$mention_watch_name_mention_default		= "<@$author_id>";
			$mention_watch_name_queue_default							= $mention_watch_name_mention_default."is watching the following users:" . PHP_EOL;
			$mention_watch_name_queue_full 								= "";
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "watch ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
//				Place watch info in target's folder
				$watchers[] = VarLoad($guild_folder."/".$mention_id, "$watchers.php");
				$watchers = array_unique($arr);
				$watchers[] = $author_id;
				VarSave($guild_folder."/".$mention_id, "watchers.php", $watchers);
				$mention_watch_name_queue 								= "**<@$mention_id>** ";
				$mention_watch_name_queue_full 							= $mention_watch_name_queue_full . PHP_EOL . $mention_watch_name_queue;
			}
//			Send a message
			if ($mention_watch_name_queue != ""){
				if ($watch_channel)$watch_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
				else $message->reply($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
//				React to the original message
//				if($react) $message->react("👀");
				if($react) $message->react("👁");		
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;
			}
//						
		}
		
		if ($creator || $owner || $dev || $admin || $mod) //Only allow these roles to use this
		if (substr($message_content_lower, 0, 9) == $command_symbol . 'unwatch '){ //;unwatch @
			echo "REMOVING WATCH ON TARGETS MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			$mention_watch_name_queue_default							= "<@$author_id> is no longer watching the following users:" . PHP_EOL;
			$mention_watch_name_queue_full 								= "";
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "unwatch ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
//				Place watch info in target's folder
				$watchers[] = VarLoad($guild_folder."/".$mention_id, "$watchers.php");
				$watchers = array_value_remove($author_id, $watchers);
				VarSave($guild_folder."/".$mention_id, "watchers.php", $watchers);
				$mention_watch_name_queue 								= "**<@$mention_id>** ";
				$mention_watch_name_queue_full 							= $mention_watch_name_queue_full . PHP_EOL . $mention_watch_name_queue;
			}
//			React to the original message
			if($react) $message->react("👍");
//			Send the message
			if ($watch_channel)	$watch_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
			else $author_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
			return true;
		}
		
		if ($creator || $owner || $dev || $admin || $mod) //Only allow these roles to use this
		if ( (substr($message_content_lower, 0, 8) == $command_symbol . 'vwatch ') || (substr($message_content_lower, 0, 4) == $command_symbol . 'vw ')){ //;vwatch @
			echo "VERIFYING AND WATCHING TARGET MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if ($watch_channel)	$mention_watch_name_mention_default		= "<@$author_id>";
			$mention_watch_name_queue_default							= $mention_watch_name_mention_default."is watching the following users:" . PHP_EOL;
			$mention_watch_name_queue_full 								= "";
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "vwatch ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				$filter = "$command_symbol" . "vw ";
				$value = str_replace($filter, "", $value);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				
//				Place watch info in target's folder
				$watchers[] = VarLoad($guild_folder."/".$mention_id, "$watchers.php");
				$watchers = array_unique($arr);
				$watchers[] = $author_id;
				VarSave($guild_folder."/".$mention_id, "watchers.php", $watchers);
				$mention_watch_name_queue 								= "**<@$mention_id>** ";
				$mention_watch_name_queue_full 							= $mention_watch_name_queue_full . PHP_EOL . $mention_watch_name_queue;
				
				echo "mention_id: " . $mention_id . PHP_EOL;
				$target_guildmember 									= $message->guild->members->get($mention_id);
				$target_guildmember_role_collection 					= $target_guildmember->roles;									//echo "target_guildmember_role_collection: " . (count($author_guildmember_role_collection)-1);
				
//				Populate arrays of the info we need
				$target_verified										= false; //Default
				$x=0;
				foreach ($target_guildmember_role_collection as $role){
					if ($x!=0){ //0 is @everyone so skip it
						if ($role->id == $role_verified_id)
							$target_verified 							= true;
					}
					$x++;
				}
				
				if($target_verified == false){
//					Build the string for the reply
					$mention_role_name_queue 							= "**<@$mention_id>** ";
					$mention_role_name_queue_full 						= $mention_role_name_queue_full . PHP_EOL . $mention_role_name_queue;
//					Add the verified role to the member
					$target_guildmember->addRole($role_verified_id)->done(
						function (){
							//if ($general_channel) $general_channel->send('Welcome to the Palace, <@$mention_id>! Feel free to pick out some roles in #role-picker!');
						},
						function ($error) {
							throw $error;
						}
					);
					echo "Verify role added to $mention_id" . PHP_EOL;
				}
			}
//			Send a message
			if ($mention_watch_name_queue != ""){
				if ($watch_channel)$watch_channel->send($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
				else $message->reply($mention_watch_name_queue_default . $mention_watch_name_queue_full . PHP_EOL);
//				React to the original message
//				if($react) $message->react("👀");
				if($react) $message->react("👁");
				if($general_channel){
					$msg = "Welcome to the Palace, <@$mention_id>!";
					if ($rolepicker_channel_id != "" && $rolepicker_channel_id != NULL) $msg = $msg . " Feel free to pick out some roles in <#$rolepicker_channel_id>.";
					if($general_channel)$general_channel->send($msg);
				}
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;
			}
		}
		
		if ($creator || $owner || $dev || $admin || $mod)
		if (substr($message_content_lower, 0, 6) == $command_symbol . 'warn '){ //;warn @
			echo "WARN TARGETS MENTIONED" . PHP_EOL;
			//$message->reply("Not yet implemented!");
//			Get an array of people mentioned
			$mentions_arr 												= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			if ($modlog_channel)	$mention_warn_name_mention_default		= "<@$author_id>";
			$mention_warn_queue_default									= $mention_warn_name_mention_default."warned the following users:" . PHP_EOL;
			$mention_warn_queue_full 									= "";
			
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
				$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
				$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
				$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
				$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
				$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
				$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
				
//				Build the string to log
				$filter = "$command_symbol" . "warn <@!$mention_id>";
				$warndate = date("m/d/Y");
				$mention_warn_queue 									= "**$mention_check warned $author_check on $warndate for reason: **" . str_replace($filter, "", $message_content);
				
//				Place warn info in target's folder
				$infractions = VarLoad($guild_folder."/".$mention_id, "infractions.php");
				$infractions[] = $mention_warn_queue;
				VarSave($guild_folder."/".$mention_id, "infractions.php", $infractions);
				$mention_warn_queue_full 								= $mention_warn_queue_full . PHP_EOL . $mention_warn_queue;
			}
//			Send a message
			if ($mention_warn_queue != ""){
				if ($watch_channel)$watch_channel->send($mention_warn_queue_default . $mention_warn_queue_full . PHP_EOL);
				else $message->reply($mention_warn_queue_default . $mention_warn_queue_full . PHP_EOL);
//				React to the original message
//				if($react) $message->react("👀");
				if($react) $message->react("👁");		
				return true;
			}else{
				if($react) $message->react("👎");
				$message->reply("Nobody in the guild was mentioned!");
				return true;
			}
		}
		
		if ($creator || $owner || $dev || $admin || $mod)
		if (substr($message_content_lower, 0, 13) == $command_symbol . 'infractions '){ //;infractions @
			echo "GET INFRACTIONS FOR TARGET MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 													= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "infractions ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			$x = 0;
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
				if ($x == 0){ //We only want the first person mentioned
	//				id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
					$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
					$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
					
	//				Place infraction info in target's folder
					$infractions = VarLoad($guild_folder."/".$mention_id, "infractions.php");
					$y = 0;
                    $mention_infraction_queue = "";
                    $mention_infraction_queue_full = "";
					foreach ( $infractions as $infraction ){
						//Build a string
						$mention_infraction_queue = $mention_infraction_queue . "$y: " . $infraction . PHP_EOL;
						$y++;
					}
					$mention_infraction_queue_full 								= $mention_infraction_queue_full . PHP_EOL . $mention_infraction_queue;
				}
				$x++;
			}
//			Send a message
			if ($mention_infraction_queue != ""){
				$length = strlen($mention_infraction_queue_full);
				if ($length < 1025){
 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
//					->setTitle("Commands")																	// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//					->setDescription("Infractions for $mention_check")										// Set a description (below title, above fields)
					->addField("Infractions for $mention_check", "$mention_infraction_queue_full")			// New line after this
//					->addField("⠀", "Use '" . $command_symbol . "removeinfraction @mention #' to remove")	// New line after this
					
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
//					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
//					->setAuthor("$author_check", "$author_guild_avatar")  									// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
//					Send the embed to the author's channel
					$author_channel->send('', array('embed' => $embed))->done(null, function ($error){
						echo $error.PHP_EOL; //Echo any errors
					});
					return true;
				}else{ //Too long, send reply instead of embed
					$message->reply($mention_infraction_queue_full . PHP_EOL);
//					React to the original message
//					if($react) $message->react("👀");
					if($react) $message->react("🗒️");		
					return true;
				}
			}else{
				//if($react) $message->react("👎");
				$message->reply("No infractions found!");
				return true;
			}
		}
		
		if ($creator || $owner || $admin)
		if (substr($message_content_lower, 0, 18) == $command_symbol . 'removeinfraction '){ //;removeinfractions @mention #
			echo "GET INFRACTIONS FOR TARGET MENTIONED" . PHP_EOL;
//			Get an array of people mentioned
			$mentions_arr 													= $message->mentions->users; 									//echo "mentions_arr: " . PHP_EOL; var_dump ($mentions_arr); //Shows the collection object
			
			if (!strpos($message_content_lower, "<")){ //String doesn't contain a mention
				$filter = "$command_symbol" . "removeinfraction ";
				$value = str_replace($filter, "", $message_content_lower);
				$value = str_replace("<@!", "", $value); $value = str_replace("<@", "", $value); $value = str_replace("<@", "", $value); 
				$value = str_replace(">", "", $value);
				if(is_numeric($value)){
					$mention_member				= $author_guild->members->get($value);
					$mention_user				= $mention_member->user;
					$mentions_arr				= array($mention_user);
				}else return $message->reply("Invalid input! Please enter a valid ID or @mention the user");
				if ($mention_member == NULL) return $message->reply("Invalid input! Please enter an ID or @mention the user");
			}
			
			$x = 0;
			foreach ( $mentions_arr as $mention_param ){																				//echo "mention_param: " . PHP_EOL; var_dump ($mention_param);
				if ($x == 0){ //We only want the first person mentioned
//					id, username, discriminator, bot, avatar, email, mfaEnabled, verified, webhook, createdTimestamp
					$mention_param_encode 									= json_encode($mention_param); 									//echo "mention_param_encode: " . $mention_param_encode . PHP_EOL;
					$mention_json 											= json_decode($mention_param_encode, true); 					//echo "mention_json: " . PHP_EOL; var_dump($mention_json);
					$mention_id 											= $mention_json['id']; 											//echo "mention_id: " . $mention_id . PHP_EOL; //Just the discord ID
					$mention_username 										= $mention_json['username']; 									//echo "mention_username: " . $mention_username . PHP_EOL; //Just the discord ID
					$mention_discriminator 									= $mention_json['discriminator']; 								//echo "mention_discriminator: " . $mention_discriminator . PHP_EOL; //Just the discord ID
					$mention_check 											= $mention_username ."#".$mention_discriminator; 				//echo "mention_check: " . $mention_check . PHP_EOL; //Just the discord ID
					
//					Get infraction info in target's folder
					$infractions = VarLoad($guild_folder."/".$mention_id, "infractions.php");
					$proper = $command_symbol."removeinfraction <@!$mention_id> ";
					$strlen = strlen($command_symbol."removeinfraction <@!$mention_id> ");
					$substr = substr($message_content_lower, $strlen);
					
//					Check that message is formatted properly
					if ($proper != substr($message_content_lower, 0, $strlen)){
						$message->reply("Please format your command properly: ;warn @mention number");
						return true;
					}
					
//					Check if $substr is a number
					if ( ($substr != "") && (is_numeric(intval($substr))) ){
//						Remove array element and reindex
						//array_splice($infractions, $substr, 1);
						if ($infractions[$substr] != NULL){
							$infractions[$substr] = "Infraction removed by $author_check on " . date("m/d/Y"); // for arrays where key equals offset
//							Save the new infraction log
							VarSave($guild_folder."/".$mention_id, "infractions.php", $infractions);
							
//							Send a message
							if($react) $message->react("👍");
							$message->reply("Infraction $substr removed from $mention_check!");
							return true;
						}else{
							if($react) $message->react("👎");
							$message->reply("Infraction '$substr' not found!");
							return true;
						}
						
					}else{
						if($react) $message->react("👎");
						$message->reply("'$substr' is not a number");
						return true;
					}
					
				}
				$x++;
			}
		}
		
	}); //end message function
		
	$discord->on('guildMemberAdd', function ($guildmember){ //Handling of a member joining the guild
		echo "guildMemberAdd" . PHP_EOL;
		$user = $guildmember->user;
		$welcome = true;
		
		$user_username 											= $user->username; 													//echo "author_username: " . $author_username . PHP_EOL;
		$user_id 												= $user->id;														//echo "new_user_id: " . $new_user_id . PHP_EOL;
		$user_discriminator 									= $user->discriminator;												//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$user_avatar 											= $user->getAvatarURL();											//echo "author_id: " . $author_id . PHP_EOL;
		$user_check 											= "$user_username#$user_discriminator"; 							//echo "author_check: " . $author_check . PHP_EOL;\
		$user_tag												= $user->tag;
		$user_createdTimestamp									= $user->createdTimestamp;
		$user_createdTimestamp									= date("D M j H:i:s Y", $user_createdTimestamp);
		
		$guild_memberCount										= $guildmember->guild->memberCount;
		$author_guild_id										= $guildmember->guild->id;
		$author_guild_name										= $guildmember->guild->name;
		
		if($welcome === true){
			//Load config variables for the guild
			$guild_folder = "\\guilds\\$author_guild_id";
			$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
			include "$guild_config_path"; echo "guild_config_path: " . $guild_config_path . PHP_EOL;
			if($welcome_log_channel_id) 		$welcome_log_channel	= $guildmember->guild->channels->get($welcome_log_channel_id);
			if($welcome_public_channel_id) 		$welcome_public_channel	= $guildmember->guild->channels->get($welcome_public_channel_id);

//			Build the embed
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
//				->setTitle("$user_check")																			// Set a title
				->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//				->setDescription("$author_guild_name")												// Set a description (below title, above fields)
				->setDescription("<@$user_id> just joined **$author_guild_name**\n
				There are now **$guild_memberCount** members.\n
				Account created on $user_createdTimestamp")												// Set a description (below title, above fields)
				//X days agow
//				->setAuthor("$user_check", "$author_guild_avatar")  									// Set an author with icon
//				->addField("Roles", 		"$author_role_name_queue_full")											// New line after this
				
				->setThumbnail("$user_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");
			
			if($welcome_log_channel){ //Send a detailed embed with user info
				$welcome_log_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
			}elseif($modlog_channel){ //Send a detailed embed with user info
				$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
			}
			if($welcome_public_channel){ //Greet the new user to the server
				$welcome_public_channel->send("Welcome <@$user_id> to $author_guild_name!");
			}
		}
		
		$user_folder = "users/$user_id";
		CheckDir($user_folder);
		//Place user info in target's folder
		$array = VarLoad($user_folder, "tags.php");
		if (!in_array($user_tag, $array)) $array[] = $user_tag;
		VarSave($user_folder, "tags.php", $array);
		
		return true;	
	}); //end guildMemberAdd function
	
	$discord->on('guildMemberUpdate', function ($member_new, $member_old){ //Handling of a member getting updated
		echo "guildMemberUpdate" . PHP_EOL;
		$member_id			= $member_new->id;
		$member_guild		= $member_new->guild;
		$new_user			= $member_new->user;
		$old_user			= $member_old->user;
		
		$user_folder			= "users/$member_id";
		CheckDir($user_folder);
		
		$author_guild_id = $member_new->guild->id;
		$guild_folder = "\\guilds\\$author_guild_id";
		if(!CheckDir($guild_folder)){
			if(!CheckFile($guild_folder, "guild_owner_id.php")){
				VarSave($guild_folder, "guild_owner_id.php", $guild_owner_id);
			}else $guild_owner_id	= VarLoad($guild_folder, "guild_owner_id.php");
		}
		//Load config variables for the guild
		$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
		include "$guild_config_path";
		
		$modlog_channel		= $member_guild->channels->get($modlog_channel_id);
		
//		Member properties
		$new_roles			= $member_new->roles;
		$new_displayName	= $member_new->displayName;
		
		$old_roles			= $member_old->roles;
		$old_displayName	= $member_old->displayName;
		
//		User properties
		$new_tag			= $new_user->tag;
		$new_avatar			= $new_user->getAvatarURL();
		
		$old_tag			= $old_user->tag;
		$old_avatar			= $old_user->getAvatarURL();
		
//		Populate roles
		$old_member_roles_names 											= array();
		$old_member_roles_ids 												= array();
		$x=0;
		foreach ($old_roles as $role){
			if ($x!=0){ //0 is always @everyone so skip it
				$old_member_roles_names[] 									= $role->name; 												//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
				$old_member_roles_ids[]										= $role->id; 												//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
			}
			$x++;
		}

		$new_member_roles_names 											= array();
		$new_member_roles_ids 												= array();
		$x=0;
		foreach ($new_roles as $role){
			if ($x!=0){ //0 is always @everyone so skip it
				$new_member_roles_names[] 									= $role->name; 												//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
				$new_member_roles_ids[]										= $role->id; 												//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
			}
			$x++;
		}		
		
		
//		Compare changes
		$changes = "";
		include_once "custom_functions.php";
		
		if ($old_tag != $new_tag){
			echo "old_tag: " . $old_tag . PHP_EOL;
			echo "new_tag: " . $new_tag . PHP_EOL;
			$changes = $changes . "Old tag: $old_tag\n New tag: $new_tag\n";
			
			//Place user info in target's folder
			$array = VarLoad($user_folder, "tags.php");
			if (!in_array($old_tag, $array))
				$array[] = $old_tag; 
			if (!in_array($new_tag, $array)) $array[] = $new_tag;
			VarSave($user_folder, "tags.php", $array);
		}
		
		if ($old_avatar != $new_avatar){
			echo "old_avatar: " . $old_avatar . PHP_EOL;
			echo "new_avatar: " . $new_avatar . PHP_EOL;
			$changes = $changes . "Old avatar: $old_avatar\n New avatar: $new_avatar\n";
			
			//Place user info in target's folder
			$array = VarLoad($user_folder, "avatars.php");
			if (!in_array($old_avatar, $array))
				$array[] = $old_avatar; 
			if (!in_array($new_avatar, $array)) $array[] = $new_avatar;
			VarSave($user_folder, "avatars.php", $array);
		}
		
		// ->nickname seems to return null sometimes, so use displayName instead
		if ($old_displayName != $new_displayName){
			echo "old_displayName: " . $old_displayName . PHP_EOL;
			echo "new_displayName: " . $new_displayName . PHP_EOL;
			$changes = $changes . "Nickname change:\n`$old_displayName`→`$new_displayName`\n";
			
			//Place user info in target's folder
			$array = VarLoad($user_folder, "nicknames.php");
			if (!in_array($old_displayName, $array))
				$array[] = $old_displayName; 
			if (!in_array($new_displayName, $array)) $array[] = $new_displayName;
			VarSave($user_folder, "nicknames.php", $array);
		}
		
		if ($old_member_roles_ids != $new_member_roles_ids){
//			Build the string for the reply

			/*
//			Log the full list of old and new roles
			
			$old_role_name_queue 									= "";
			foreach ($old_member_roles_ids as $old_role){
				$old_role_name_queue 								= "$old_role_name_queue<@&$old_role> ";
			}
			$old_role_name_queue 									= substr($old_role_name_queue, 0, -1);
			$old_role_name_queue_full 								= $old_role_name_queue_full . PHP_EOL . $old_role_name_queue;
			//$changes = $changes . "Old roles: $old_role_name_queue_full\n";
			
			$new_role_name_queue 									= "";
			foreach ($new_member_roles_ids as $new_role){
				$new_role_name_queue 								= "$new_role_name_queue<@&$new_role> ";
			}
			$new_role_name_queue 									= substr($new_role_name_queue, 0, -1);
			$new_role_name_queue_full 								= $new_role_name_queue_full . PHP_EOL . $new_role_name_queue;
			$new_role_name_queue_check								= trim($new_role_name_queue_full);
			//$changes = $changes . "New roles: $new_role_name_queue_full\n";
			*/
			
			
//			Only log the added/removed difference
//			New Roles
			$role_difference_ids = array_diff($old_member_roles_ids, $new_member_roles_ids);
			foreach ($role_difference_ids as $role_diff){
				if (in_array($role_diff, $old_member_roles_ids)){
					$switch = "Removed roles: ";
				}
				else{
					$switch = "Added roles: ";
				}
				$changes = $changes . $switch . "<@&$role_diff>";
			}
			//Old roles
			$role_difference_ids = array_diff($new_member_roles_ids, $old_member_roles_ids);
			foreach ($role_difference_ids as $role_diff){
				if (in_array($role_diff, $old_member_roles_ids)){
					$switch = "Removed roles: ";
				}
				else{
					$switch = "Added roles: ";
				}
				$changes = $changes . $switch . "<@&$role_diff>";
			}
		}
		
		//echo "switch: " . $switch . PHP_EOL;
		//if( ($switch != "") || ($switch != NULL)) //User was kicked (They have no roles anymore)
		if( ($modlog_channel_id != NULL) && ($modlog_channel_id != "") )
		if($changes != ""){
			//$changes = "<@$member_id>'s information has changed:\n" . $changes;
			if (strlen($changes) < 1025){
 
				$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
				$embed
//					->setTitle("Commands")																	// Set a title
					->setColor("a7c5fd")																	// Set a color (the thing on the left side)
					->setDescription("<@$member_id>\n**User Update**\n$changes")									// Set a description (below title, above fields)
//					->addField("**User Update**", "$changes")												// New line after this
					
//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
					->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
					->setAuthor("$old_tag", "$old_avatar")  												// Set an author with icon
					->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
					->setURL("");                             												// Set the URL
//				Send a message
				if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
				return true;
			}else{
				if($modlog_channel)$modlog_channel->send("**User Update**\n$changes");
				return true;
			}
		}else{ //No info we want to capture was changed
			return true;
		}
		
	});
	
	$discord->on('guildMemberRemove', function ($guildmember){ //Handling of a user leaving the guild
		echo "guildMemberRemove" . PHP_EOL;
		$user = $guildmember->user;
		$welcome = true;
		
		if($welcome === true){
			$user_username 											= $user->username; 													//echo "author_username: " . $author_username . PHP_EOL;
			$user_id 												= $user->id;														//echo "new_user_id: " . $new_user_id . PHP_EOL;
			$user_discriminator 									= $user->discriminator;												//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
			$user_avatar 											= $user->getAvatarURL();											//echo "author_id: " . $author_id . PHP_EOL;
			$user_check 											= "$user_username#$user_discriminator"; 							//echo "author_check: " . $author_check . PHP_EOL;\
			$user_createdTimestamp									= $user->createdTimestamp;
			$user_createdTimestamp									= date("D M j Y H:i:s", $user_createdTimestamp);
			
			$target_guildmember_role_collection 					= $guildmember->roles;					//This is the Role object for the GuildMember

			$target_guildmember_roles_mentions						= array();
			$x=0;
			foreach ($target_guildmember_role_collection as $role){
				if ($x!=0){ //0 is @everyone so skip it
//					$target_guildmember_roles_names[] 				= $role->name; 													//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
					$target_guildmember_roles_mentions[] 			= "<@&{$role->id}>"; 													//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
				}
				$x++;
			}
            $mention_role_id_queue = "";
			foreach ($target_guildmember_roles_mentions as $mention_role){
//				$mention_role_name_queue 							= $mention_role_name_queue . $mention_role;
				$mention_role_id_queue 								= $mention_role_id_queue . "$mention_role";
			}
			if ( ($mention_role_id_queue === NULL) || ($mention_role_id_queue == "") ){ //String cannot be empty or the embed will throw an exception
				$mention_role_id_queue = "⠀"; //Invisible unicode
			}
			
			$guild_memberCount										= $guildmember->guild->memberCount;
			$author_guild_id = $guildmember->guild->id;
			//Load config variables for the guild
			$guild_folder = "\\guilds\\$author_guild_id";
			$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
			include "$guild_config_path";
			
			try{
				if($welcome_log_channel_id) $welcome_log_channel	= $guildmember->guild->channels->get($welcome_log_channel_id);
			}catch(Exception $e){
//				RuntimeException: Unknown property																		//echo 'AUTHOR NOT IN GUILD' . PHP_EOL;
			}
			
//			Build the embed
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
//				->setTitle("Leave notification")														// Set a title
				->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//				->setDescription("$author_guild_name")													// Set a description (below title, above fields)
				->setDescription("<@$user_id> has left the server!\n
				There are now **$guild_memberCount** members.")											// Set a description (below title, above fields)
//				->setAuthor("$member_check", "$author_guild_avatar")  									// Set an author with icon
				->addField("Roles", 		"$mention_role_id_queue")									// New line after this
				
				->setThumbnail("$user_avatar")															// Set a thumbnail (the image in the top right corner)
//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')            // Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
			
			if($welcome_log_channel){
				echo "Welcome channel found!";
//				Send the message, announcing the member's departure
				$welcome_log_channel->send('', array('embed' => $embed))->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
				return true;
			}else{
				echo "No welcome channel!";
				return true;
			}
		}
	}); //end GuildMemberRemove function
		
	$discord->on('guildBanAdd', function ($guild, $user){ //Handling of a user getting banned
		echo "guildBanAdd" . PHP_EOL;
		//
	});
	
	$discord->on('guildBanRemove', function ($guild, $user){ //Handling of a user getting unbanned
		echo "guildBanRemove" . PHP_EOL;
		//
	});
	
	$discord->on('messageUpdate', function ($message_new, $message_old){ //Handling of a message being changed
		//This event listener gets triggered willy-nilly so we need to do some checks here if we want to get anything useful out of it
		//If the timestamp is older than timestampSetup nothing will be passed to this method, use messageUpdateRaw instead
		
		$message_content_new = $message_new->content; //Null if message is too old
		$message_content_old = $message_old->content; //Null if message is too old
		$message_id_new = $message_new->id; //This doesn't match any message id if the message is too old
		
		//Only process message changes
		if ($message_content_new === $message_content_old){
			echo "NO MESSAGE CONTENT CHANGE OR MESSAGE TOO OLD" . PHP_EOL;
			return true;
		}
		
		//Make sure the messages aren't blank
		if (($message_content_new == NULL) || ($message_content_new == "")) { //This should never trigger, but just in case...
			echo "BLANK OR OLD MESSAGE EDITED" . PHP_EOL;
			return true;
		}
		
		echo "messageUpdate" . PHP_EOL;

		/*
		Debug output
		
//		Check the timestamp
		$createdTimestamp_new = $message_new->createdTimestamp;
		$createdTimestamp_old = $message_old->createdTimestamp;
		
		$editedTimestamp_new = $message_new->editedTimestamp;
		$editedTimestamp_old = $message_old->editedTimestamp;
		
		echo "message_content_new: " . $message_content_new . PHP_EOL;
		echo "message_content_old: " . $message_content_old . PHP_EOL;
		echo PHP_EOL;
		echo "createdTimestamp_new: " . $createdTimestamp_new . PHP_EOL;
		echo "createdTimestamp_old: " . $createdTimestamp_old . PHP_EOL;
		echo PHP_EOL;
		echo "editedTimestamp_new: " . $editedTimestamp_new . PHP_EOL;
		echo "editedTimestamp_old: " . $editedTimestamp_old . PHP_EOL;
		echo PHP_EOL;
		*/
		
		//Load global variables
		$guild = $message_new->guild;
		$author_guild_id = $guild->id;
		//Load config variables for the guild
		$guild_folder = "\\guilds\\$author_guild_id";
		$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
		require "$guild_config_path";
		
		$modlog_channel	= $guild->channels->get($modlog_channel_id);
		
		//Load author info
		$author_user				= $message_new->author; //User object
		$author_channel 			= $message_new->channel;
		$author_channel_id			= $author_channel->id; 												//echo "author_channel_id: " . $author_channel_id . PHP_EOL;
		$author_channel_class		= get_class($author_channel);
		$is_dm = false;
		if ($author_channel_class === "CharlotteDunois\Yasmin\Models\DMChannel"){ //True if direct message
			$is_dm = true;
			return true; //Don't try and process direct messages
		}
		$author_username 			= $author_user->username; 											//echo "author_username: " . $author_username . PHP_EOL;
		$author_discriminator 		= $author_user->discriminator;										//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$author_id 					= $author_user->id;													//echo "author_id: " . $author_id . PHP_EOL;
		$author_avatar 				= $author_user->getAvatarURL();										//echo "author_avatar: " . $author_avatar . PHP_EOL;
		$author_check 				= "$author_username#$author_discriminator"; 						//echo "author_check: " . $author_check . PHP_EOL;
		
		$changes = "";
		//if (($message_content_new != $message_content_old) || (($message_content_old == "") || ($message_content_old == NULL))) 	{		
		if ($message_content_new != $message_content_old){		
			
//			Build the string for the reply
			$changes = $changes . "**Message ID:**: $message_id_new\n";
			$changes = $changes . "**Channel:** <#$author_channel_id>\n";
			$changes = $changes . "**Before:** $message_content_old\n";
			$changes = $changes . "**After:** $message_content_new\n";

		}
		
		if($modlog_channel)
		if ($changes != ""){
			//Build the embed
			//$changes = "**Message edit**:\n" . $changes;
			
//			Build the embed message
			$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
			$embed
//				->setTitle("Commands")																	// Set a title
				->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//				->setDescription("Commands for $author_guild_name")									// Set a description (below title, above fields)
				->addField("Message Update", "$changes")												// New line after this
				
//				->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//				->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
				->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
				->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
				->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
				->setURL("");                             												// Set the URL
//			Send the message
//			We do not need another promise here, so we call done, because we want to consume the promise
			if($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
				echo $error.PHP_EOL; //Echo any errors
			});
			return true;
		}else{ //No info we want to check was changed
			return true;
		}
	});
	
	$discord->on('messageUpdateRaw', function ($channel, $data_array){ //Handling of an old/uncached message being changed		
		$type				= $data_array['type'];
		$tts				= $data_array['tts'];
		$timestamp			= $data_array['timestamp'];
		$pinned				= $data_array['pinned'];
		$nonce				= $data_array['nonce'];
		$mentions			= $data_array['mentions'];
		$mention_roles		= $data_array['mention_roles'];
		$mention_everyone	= $data_array['mention_everyone'];
		$member				= $data_array['member']; //echo "member: " . var_dump($member) . PHP_EOL; //username, id, discriminator,avatar
		$id					= $data_array['id']; echo "id: " . var_dump($id) . PHP_EOL; //username, id, discriminator,avatar
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
		
		$log_message = "**Message ID:** $id\n**Channel:** <#$channel_id>\n**New content:** $content" . PHP_EOL;
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
	});
	
	$discord->on('messageDelete', function ($message){ //Handling of a message being deleted
		echo "messageDelete" . PHP_EOL;
		//id, author, channel, guild, member
		//createdAt, editedAt, createdTimestamp, editedTimestamp, content, cleanContent, attachments, embeds, mentions, pinned, type, reactions, webhookID
		$message_content												= $message->content;
		$message_id														= $message->id;
		if ( ($message_content == NULL) || ($message_content == "") ){
			echo "BLANK MESSAGE DELETED" . PHP_EOL;
			return true;
		}			//Don't process blank messages, bots, or webhooks
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
		
//		Build the embed
		$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
		$embed
//			->setTitle("$user_check")																// Set a title
			->setColor("a7c5fd")																	// Set a color (the thing on the left side)
//			->setDescription("$author_guild_name")												// Set a description (below title, above fields)
			->setDescription("$log_message")														// Set a description (below title, above fields)
			//X days ago
			->setAuthor("$author_check ($author_id)", "$author_avatar")  							// Set an author with icon
//			->addField("Roles", 		"$author_role_name_queue_full")								// New line after this
			
			->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
//			->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
			->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
			
			->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
			->setURL("");
//			Send the message
//			We do not need another promise here, so we call done, because we want to consume the promise
		if ($modlog_channel)$modlog_channel->send('', array('embed' => $embed))->done(null, function ($error){
			echo $error.PHP_EOL; //Echo any errors
		});
		return true; //No more processing, we only want to process the first person mentioned
	});
	
	$discord->on('messageDeleteRaw', function ($channel, $message_id){ //Handling of an old/uncached message being deleted
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
	});
	
	$discord->on('messageDeleteBulk', function ($messages){ //Handling of multiple messages being deleted
		echo "messageDeleteBulk" . PHP_EOL;
		//
	});
	
	$discord->on('messageDeleteBulkRaw', function ($messages){ //Handling of multiple old/uncached messages being deleted
		//
	});
	
	$discord->on('messageReactionAdd', function ($reaction, $respondent_user){ //Handling of a message being reacted to
		$me = $reaction->me;
		if ($me === true){ //Don't process reactions this bot makes
			echo "MESSAGE REACTION ADDED" . PHP_EOL;
			return true;
		}
		echo "messageReactionAdd" . PHP_EOL;
		
		//Load message info
		$message					= $reaction->message;
		$message_content			= $message->content;
		if ( ($message_content == NULL) || ($message_content == "") ) return true; //Don't process blank messages, bots, webhooks, or rich embeds
		$message_content_lower = strtolower($message_content);
		
		//Load guild info
		$guild						= $message->guild;
		$author_guild				= $message->guild; //Redeclared only for checkdir... Really need to fix this (TODO)
		$author_guild_id			= $author_guild->id;
		//Create a folder for the guild if it doesn't exist already
		$guild_folder = "\\guilds\\$author_guild_id";
		CheckDir($guild_folder);
		//Load config variables for the guild
		$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
		include "$guild_config_path";
		
		//Role picker stuff
		$message_id					= $message->id;														//echo "message_id: " . $message_id . PHP_EOL;
		GLOBAL $species, $species2, $sexualities, $gender, $custom_roles;
		
		//Load author info
		$author_user				= $message->author; //User object
		$author_channel 			= $message->channel;
		$author_channel_id			= $author_channel->id; 												//echo "author_channel_id: " . $author_channel_id . PHP_EOL;
		$author_channel_class		= get_class($author_channel);
		$is_dm = false;
		if ($author_channel_class === "CharlotteDunois\Yasmin\Models\DMChannel"){ //True if direct message
			$is_dm = true;
			return true; //Don't try and process direct messages
		}
		$author_username 			= $author_user->username; 											//echo "author_username: " . $author_username . PHP_EOL;
		$author_discriminator 		= $author_user->discriminator;										//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$author_id 					= $author_user->id;													//echo "author_id: " . $author_id . PHP_EOL;
		$author_avatar 				= $author_user->getAvatarURL();										//echo "author_avatar: " . $author_avatar . PHP_EOL;
		$author_check 				= "$author_username#$author_discriminator"; 						//echo "author_check: " . $author_check . PHP_EOL;
		$author_folder				= $author_guild_id."/".$author_id;
		
		//Load respondent info
		$respondent_username 		= $respondent_user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
		$respondent_discriminator 	= $respondent_user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$respondent_id 				= $respondent_user->id;												//echo "author_id: " . $author_id . PHP_EOL;
		$respondent_avatar 			= $respondent_user->getAvatarURL();									//echo "author_avatar: " . $author_avatar . PHP_EOL;
		$respondent_check 			= "$respondent_username#$respondent_discriminator"; 				//echo "author_check: " . $author_check . PHP_EOL;
		$respondent_member 			= $author_guild->members->get($respondent_id);
		
		//Load emoji info
		//guild, user
		//animated, managed, requireColons
		//createdTimestamp, createdAt
		$emoji						= $reaction->emoji;
		$emoji_id					= $emoji->id;			echo "emoji_id: " . $emoji_id . PHP_EOL; //Unicode if null
		
		$unicode					= false;
		if ($emoji_id === NULL)
						$unicode 	= true;					echo "unicode: " . $unicode . PHP_EOL;
		$emoji_name					= $emoji->name;			echo "emoji_name: " . $emoji_name . PHP_EOL;
		$emoji_identifier			= $emoji->identifier;	echo "emoji_identifier: " . $emoji_identifier . PHP_EOL;
		
		if ($unicode) $response = "$emoji_name";
		else $response = "<:$emoji_identifier>";
		
		
		echo "$author_check's message was reacted to by $respondent_check" . PHP_EOL;
		
		//Check rolepicker option
		GLOBAL $rolepicker_option, $species_option, $sexuality_option, $gender_option, $custom_option;
		if ( ($rolepicker_id != "") || ($rolepicker_id != NULL) ){
			if(!CheckFile($guild_folder, "rolepicker_option.php"))				$rp0	= $rolepicker_option;										//Species role picker
			else 														$rp0	= VarLoad($guild_folder, "rolepicker_option.php");
		} else $rp0 = false;
		
		if($rp0 === true)
		if($author_id == $rolepicker_id){
			//Check options
			if ( ($species_message_id != "") || ($species_message_id != NULL) ){
				if(!CheckFile($guild_folder, "species_option.php"))				$rp1	= $species_option;										//Species role picker
				else 													$rp1	= VarLoad($guild_folder, "species_option.php");
			} else $rp1 = false;
			if ( ($sexuality_message_id != "") || ($sexuality_message_id != NULL) ){
				if(!CheckFile($guild_folder, "sexuality_option.php"))			$rp2	= $sexuality_option;										//Sexuality role picker
				else 													$rp2	= VarLoad($guild_folder, "sexuality_option.php");
			} else $rp2 = false;
			if ( ($gender_message_id != "") || ($gender_message_id != NULL) ){
				if(!CheckFile($guild_folder, "gender_option.php"))				$rp3	= $gender_option;										//Gender role picker
				else 													$rp3	= VarLoad($guild_folder, "gender_option.php");
			} else $rp3 = false;
			if ( ($customroles_message_id != "") || ($customroles_message_id != NULL) ){
				if(!CheckFile($guild_folder, "customrole_option.php"))			$rp4	= $custom_option;										//Custom role picker
				else 													$rp4	= VarLoad($guild_folder, "customrole_option.php");
			} else $rp4 = false;
			//Load guild roles info
			$guild_roles													= $guild->roles;
			$guild_roles_names 												= array();
			$guild_roles_ids 												= array();
			$x=0;
			foreach ($guild_roles as $role){
				if ($x!=0){ //0 is always @everyone so skip it
					$guild_roles_names[] 									= strtolower($role->name); 				//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
					$guild_roles_ids[]										= $role->id; 				//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
				}
				$x++;
			}
			//Load respondent roles info
			$respondent_member_role_collection 								= $respondent_member->roles;
			$respondent_member_roles_names 									= array();
			$respondent_member_roles_ids 									= array();
			$x=0;
			foreach ($respondent_member_role_collection as $role){
				if ($x!=0){ //0 is @everyone so skip it
					$respondent_member_roles_names[] 						= strtolower($role->name); 	//echo "role[$x] name: " . PHP_EOL; //var_dump($role->name);
					$respondent_member_roles_ids[] 							= $role->id; 				//echo "role[$x] id: " . PHP_EOL; //var_dump($role->id);
				}
				$x++;
			}
			
			//Process the reaction to add a role
			echo "message_id: " . $message_id . PHP_EOL;
			$select_name = "";
			switch ($message_id) {
				case ($species_message_id):
					if($rp1){
						echo "species reaction" . PHP_EOL;
						foreach ($species as $var_name => $value){
							if ( ($value == $emoji_name) || ($value == $emoji_name) ){
								$select_name = $var_name;
								echo "select_name: " . $select_name . PHP_EOL;
								if(!in_array(strtolower($select_name), $guild_roles_names)){//Check to make sure the role exists in the guild
									//Create the role
									$new_role = array(
										'name' => ucfirst($select_name),
										'permissions' => 0,
										'color' => 15158332,
										'hoist' => false,
										'mentionable' => false
									);
									$guild->createRole($new_role);
									echo "Role created" . PHP_EOL;
								}
								//Messages can have a max of 20 different reacts, but species has more than 20 options
								//Clear reactions to avoid discord ratelimit
								//$message->clearReactions(); 
							}
						}
						//$message->clearReactions();
						foreach ($species as $var_name => $value){
							//$message->react($value);
						}
						
					}
					break;
				case ($species2_message_id):
					if($rp1){
						echo "species2 reaction" . PHP_EOL;
						foreach ($species2 as $var_name => $value){
							if ( ($value == $emoji_name) || ($value == $emoji_name) ){
								$select_name = $var_name;
								echo "select_name: " . $select_name . PHP_EOL;
								if(!in_array(strtolower($select_name), $guild_roles_names)){//Check to make sure the role exists in the guild
									//Create the role
									$new_role = array(
										'name' => ucfirst($select_name),
										'permissions' => 0,
										'color' => 15158332,
										'hoist' => false,
										'mentionable' => false
									);
									$guild->createRole($new_role);
									echo "Role created" . PHP_EOL;
								}
								//Messages can have a max of 20 different reacts, but species has more than 20 options
								//Clear reactions to avoid discord ratelimit
								//$message->clearReactions(); 
							}
						}
						//$message->clearReactions();
						foreach ($species as $var_name => $value){
							//$message->react($value);
						}
						
					}
					break;
				case ($sexuality_message_id):
					if ($rp2){
						echo "sexuality reaction" . PHP_EOL;
						foreach ($sexualities as $var_name => $value){
							if ( ($value == $emoji_name) || ($value == $emoji_name) ){
								$select_name = $var_name;
								if(!in_array(strtolower($select_name), $guild_roles_names)){//Check to make sure the role exists in the guild
									//Create the role
									$new_role = array(
										'name' => ucfirst($select_name),
										'permissions' => 0,
										'color' => 7419530,
										'hoist' => false,
										'mentionable' => false
									);
									$guild->createRole($new_role);
									echo "Role created" . PHP_EOL;
								}
							}
						}
						foreach ($sexualities as $var_name => $value){
							//$message->react($value);
						}
					}
					break;
				case ($gender_message_id):
					if($rp3){
						echo "gender reaction" . PHP_EOL;
						foreach ($gender as $var_name => $value){
							if ( ($value == $emoji_name) || ($value == $emoji_name) ){
								$select_name = $var_name;
								if(!in_array(strtolower($select_name), $guild_roles_names)){//Check to make sure the role exists in the guild
									//Create the role
									$new_role = array(
										'name' => ucfirst($select_name),
										'permissions' => 0,
										'color' => 3066993,
										'hoist' => false,
										'mentionable' => false
									);
									$guild->createRole($new_role);
									echo "Role created" . PHP_EOL;
								}
							}
						}
						//$message->clearReactions();
						foreach ($gender as $var_name => $value){
							//$message->react($value);
						}
					}
					break;
				case ($customrole_message_id):
					if($rp4){
						echo "Custom role reaction" . PHP_EOL;
						foreach ($custom_roles as $var_name => $value){
							if ( ($value == $emoji_name) || ($value == $emoji_name) ){
								$select_name = $var_name;
								if(!in_array(strtolower($select_name), $guild_roles_names)){//Check to make sure the role exists in the guild
									//Create the role
									$new_role = array(
										'name' => ucfirst($select_name),
										'permissions' => 0,
										'color' => 3066993,
										'hoist' => false,
										'mentionable' => false
									);
									$guild->createRole($new_role);
									echo "Role created" . PHP_EOL;
								}
							}
						}
						//$message->clearReactions();
						foreach ($custom_roles as $var_name => $value){
							//$message->react($value);
						}
					}
					break;
			}
			if($select_name != ""){ //A reaction role was found
				//Check if the member has a role of the same name
				if(in_array(strtolower($select_name), $respondent_member_roles_names)){
					//Remove the role
					$role_index = array_search(strtolower($select_name), $guild_roles_names);
					$target_role_id = $guild_roles_ids[$role_index]; echo "target_role_id: " . $target_role_id . PHP_EOL;
					$respondent_member->removeRole($target_role_id);
					echo "Role removed: $select_name" . PHP_EOL;
				}else{
					echo "Respondent does not already have the role" . PHP_EOL;
					if(in_array(strtolower($select_name), $guild_roles_names)){//Check to make sure the role exists in the guild
						//Add the role
						$role_index = array_search(strtolower($select_name), $guild_roles_names);
						$target_role_id = $guild_roles_ids[$role_index];
						$respondent_member->addRole($target_role_id);
						echo "Role added: $select_name" . PHP_EOL;
					}else{
						echo "Guild does not have this role" . PHP_EOL;
					}
				}
			}
		}
	});
	
	$discord->on('messageReactionRemove', function ($reaction, $respondent_user){ //Handling of a message reaction being removed
		$me = $reaction->me;
		if ($me === true){ //Don't process reactions this bot makes
			echo "MESSAGE REACTION REMOVED" . PHP_EOL;
			return true;
		}
		//echo "messageReactionRemove" . PHP_EOL;		
		GLOBAL $bot_id;
		
//		Load message info
		$message					= $reaction->message;
		$message_content			= $message->content;
		$message_id                 = $message->id;
		if ( ($message_content == NULL) || ($message_content == "") ) return true; //Don't process blank messages, bots, webhooks, or rich embeds
		$message_content_lower = strtolower($message_content);
		
//		Load author info
		$author_user				= $message->author; //User object
		$author_channel 			= $message->channel;
		$author_channel_id			= $author_channel->id; 												//echo "author_channel_id: " . $author_channel_id . PHP_EOL;
		$author_channel_class		= get_class($author_channel);
		$is_dm = false;
		if ($author_channel_class === "CharlotteDunois\Yasmin\Models\DMChannel"){ //True if direct message
			$is_dm = true;
			return true; //Don't try and process direct messages
		}
		$author_username 			= $author_user->username; 											//echo "author_username: " . $author_username . PHP_EOL;
		$author_discriminator 		= $author_user->discriminator;										//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$author_id 					= $author_user->id;													//echo "author_id: " . $author_id . PHP_EOL;
		$author_avatar 				= $author_user->getAvatarURL();										//echo "author_avatar: " . $author_avatar . PHP_EOL;
		$author_check 				= "$author_username#$author_discriminator"; 						//echo "author_check: " . $author_check . PHP_EOL;
		
		//Load respondent info
		$respondent_username 		= $respondent_user->username; 										//echo "author_username: " . $author_username . PHP_EOL;
		$respondent_discriminator 	= $respondent_user->discriminator;									//echo "author_discriminator: " . $author_discriminator . PHP_EOL;
		$respondent_id 				= $respondent_user->id;												//echo "author_id: " . $author_id . PHP_EOL;
		$respondent_avatar 			= $respondent_user->getAvatarURL();									//echo "author_avatar: " . $author_avatar . PHP_EOL;
		$respondent_check 			= "$respondent_username#$respondent_discriminator"; 				//echo "author_check: " . $author_check . PHP_EOL;
				
		//Load emoji info
		//guild, user
		//animated, managed, requireColons
		//createdTimestamp, createdAt
		$emoji						= $reaction->emoji;
		$emoji_id					= $emoji->id;			//echo "emoji_id: " . $emoji_id . PHP_EOL; //Unicode if null
		
		$unicode					= false;
		if ($emoji_id === NULL)
						$unicode 	= true;					//echo "unicode: " . $unicode . PHP_EOL;
		$emoji_name					= $emoji->name;			//echo "emoji_name: " . $emoji_name . PHP_EOL;
		$emoji_identifier			= $emoji->identifier;	//echo "emoji_identifier: " . $emoji_identifier . PHP_EOL;
		
		if ($unicode) $response = "$emoji_name";
		else $response = "<:$emoji_identifier>";
		
		//Do things here
		echo "$respondent_check removed their reaction from $author_check's message" . PHP_EOL;
		if ($author_id == $bot_id){ //Message reacted to belongs to this bot
			/*
			*********************
			*********************
			Remove reaction trigger
			*********************
			*********************
			*/
			switch ($message_id) {
				case 0:
					echo "" . PHP_EOL;
					break;
				case 1:
					echo "" . PHP_EOL;
					break;
				case 2:
					echo "" . PHP_EOL;
					break;
			}
		}else{
			//Do things here
		}
	});
	
	$discord->on('messageReactionRemoveAll', function ($message){ //Handling of all reactions being removed from a message
		$message_content = $message->content;
		echo "messageReactionRemoveAll" . PHP_EOL;
		//
	});
	
	$discord->on('channelCreate', function ($channel){ //Handling of a channel being created
		echo "channelCreate" . PHP_EOL;
		//
	});
	
	$discord->on('channelDelete', function ($channel){ //Handling of a channel being deleted
		echo "channelDelete" . PHP_EOL;
		//
	});
	
	$discord->on('channelUpdate', function ($channel){ //Handling of a channel being changed
		echo "channelUpdate" . PHP_EOL;
		//
	});
		
	$discord->on('userUpdate', function ($user_new, $user_old){ //Handling of a user changing their username/avatar/etc
		//This event listener will never be used for guild-related function because guildMemberUpdate already does everything we want, but is useful for logging purposes
		//For example, this will get triggered if a Nitro user changes their discriminator
		echo "userUpdate" . PHP_EOL;
		//id, username, discriminator bot, webhook, email, mfaEnabled, verified, tag, createdTimestamp, createdAt
		$user_id				= $user_new->id;
		
		$user_folder			= "users/$user_id";
		CheckDir($user_folder);
		
		$new_username			= $user_new->username;
		$new_discriminator		= $user_new->discriminator;
		$new_tag				= $user_new->tag;
		$new_avatar				= $user_new->getAvatarURL();
		
		$old_username			= $user_old->username;
		$old_discriminator		= $user_old->discriminator;
		$old_tag				= $user_old->tag;
		$old_avatar				= $user_old->getAvatarURL();
		
		$changes = "";
		
		if ($old_tag != $new_tag){
			//echo "old_tag: " . $old_tag . PHP_EOL;
			//echo "new_tag: " . $new_tag . PHP_EOL;
			$changes = $changes . "Old tag: $old_tag\nNew tag: $new_tag\n";
			
			//Place user info in target's folder
			$array = VarLoad($user_folder, "tags.php");
			if (!in_array($old_tag, $array))
				$array[] = $old_tag; 
			if (!in_array($new_tag, $array)) $array[] = $new_tag;
			VarSave($user_folder, "tags.php", $array);
		}
		
		if ($old_avatar != $new_avatar){
			//echo "old_avatar: " . $old_avatar . PHP_EOL;
			//echo "new_avatar: " . $new_avatar . PHP_EOL;
			$changes = $changes . "Old avatar: $old_avatar\nNew avatar: $new_avatar\n";
			
			//Place user info in target's folder
			VarSave($user_folder, "avatars.php", $new_avatar);
		}
		
		if($changes != ""){
			echo "<@$user_id> changed their information:\n" . $changes . PHP_EOL;	
		}
		return true;
	});
		
	$discord->on('roleCreate', function ($role){ //Handling of a role being created
		echo "roleCreate" . PHP_EOL;
		//
	});
	
	$discord->on('roleDelete', function ($role){ //Handling of a role being deleted
		echo "roleDelete" . PHP_EOL;
		//
	});
	
	$discord->on('roleUpdate', function ($role_new, $role_old){ //Handling of a role being changed
		echo "roleUpdate" . PHP_EOL;
		//
	});
	
	$discord->on('voiceStateUpdate', function ($member_new, $member_old){ //Handling of a member's voice state changing (leaves/joins/etc.)
		echo "voiceStateUpdate" . PHP_EOL;
		//
	});
	
	$discord->on('error', function ($error){ //Handling of thrown errors
		echo "ERROR: $error" . PHP_EOL;
	});
}); //end main function ready

require 'token.php'; //Token for the bot

$discord->login($token)->done();
$loop->run();
?> 
