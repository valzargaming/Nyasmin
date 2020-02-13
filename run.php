k<?php
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

include 'blacklisted_guilds.php'; //Array of Guilds that are not allowed to use this bot
include 'whitelisted_guilds.php'; //Only guilds in the $whitelisted_guilds array should be allowed to access the bot.

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
	
	$discord->on('message', function ($message) use ($discord){ //Handling of a message
		$message_content = $message->content;
		$message_id = $message->id;
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
			
			//Leave the guild if blacklisted
			GLOBAL $blacklisted_guilds;
			if ($blacklisted_guilds)
			if (in_array($author_guild_id, $blacklisted_guilds)){
				$author_guild->leave($author_guild_id)->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
			}
			//Leave the guild if not whitelisted
			GLOBAL $whitelisted_guilds;
			if ($whitelisted_guilds)
			if (!in_array($author_guild_id, $whitelisted_guilds)){
				$author_guild->leave($author_guild_id)->done(null, function ($error){
					echo $error.PHP_EOL; //Echo any errors
				});
			}

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
			$author_guild_roles 										= $author_guild->roles;
			if($getverified_channel_id) 	$getverified_channel 		= $author_guild->channels->get($getverified_channel_id);
			if($verifylog_channel_id) 		$verifylog_channel 			= $author_guild->channels->get($verifylog_channel_id); //Modlog is used if this is not declared
			if($watch_channel_id) 			$watch_channel 				= $author_guild->channels->get($watch_channel_id);
			if($modlog_channel_id) 			$modlog_channel 			= $author_guild->channels->get($modlog_channel_id);
			if($general_channel_id) 		$general_channel			= $author_guild->channels->get($general_channel_id);
			if($rolepicker_channel_id) 		$rolepicker_channel			= $author_guild->channels->get($rolepicker_channel_id);
			if($suggestion_pending_channel_id) 	$suggestion_pending_channel		= $author_guild->channels->get(strval($suggestion_pending_channel_id));
			if($suggestion_approved_channel_id) $suggestion_approved_channel	= $author_guild->channels->get(strval($suggestion_approved_channel_id));
			$author_member 												= $author_guild->members->get($author_id); 				//GuildMember object
			$author_member_roles 										= $author_member->roles; 								//Role object for the author);
		}else{ //Direct message
			if ($author_id != $discord->user->id){
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
			if ( ($customroles_message_id != "") || ($customroles_message_id != NULL) ){
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
		$author_bot = $author_user->bot;
		if ($author_bot === true) return true; //Don't process bots
		
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
			/* Deprecated
			$documentation = $documentation . "**Users:**\n";
			$documentation = $documentation . "`setup rolepicker @user` The user who posted the rolepicker messages\n";
			*/
			//Channels
			$documentation = $documentation . "**Channels:**\n";
			$documentation = $documentation . "`setup general #channel` The primary chat channel, also welcomes new users to everyone\n";
			$documentation = $documentation . "`setup welcome #channel` Simple welcome message tagging new user\n";
			$documentation = $documentation . "`setup welcomelog #channel` Detailed message about the user\n";
			$documentation = $documentation . "`setup log #channel` Detailed log channel\n"; //Modlog
			$documentation = $documentation . "`setup verify channel #channel` Detailed log channel\n";
			$documentation = $documentation . "`setup watch #channel` ;watch messages are duplicated here instead of in a DM\n";
			/* Deprecated
			$documentation = $documentation . "`setup rolepicker channel #channel` Where users pick a role\n";
			*/
			$documentation = $documentation . "`setup suggestion pending #channel` \n";
			$documentation = $documentation . "`setup suggestion approved #channel` \n";
			//Messages
			
			$documentation = $documentation . "**Messages:**\n";
			/* Deprecated
			$documentation = $documentation . "`setup species messageid`\n";
			$documentation = $documentation . "`setup species2 messageid`\n";
			$documentation = $documentation . "`setup species3 messageid`\n";
			$documentation = $documentation . "`setup sexuality messageid`\n";
			$documentation = $documentation . "`setup gender messageid`\n";
			$documentation = $documentation . "`setup customroles messageid`\n";
			*/
			$documentation = $documentation . "**Messages:**\n";
			$documentation = $documentation . "`message species`\n";
			$documentation = $documentation . "`message species2`\n";
			$documentation = $documentation . "`message species3`\n";
			$documentation = $documentation . "`message sexuality`\n";
			$documentation = $documentation . "`message gender`\n";
			$documentation = $documentation . "`message customroles`\n";
			
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
			//Send DM with current settings
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
			$documentation = $documentation . "`verify channel #channel` $getverified_channel_id\n";
			$documentation = $documentation . "`verifylog #channel` $verifylog_channel_id\n";
			$documentation = $documentation . "`watch #channel` $watch_channel_id\n";
			$documentation = $documentation . "`rolepicker channel #channel` $rolepicker_channel_id\n";
			$documentation = $documentation . "`suggestion pending #channel` $suggestion_pending_channel_id\n";
			$documentation = $documentation . "`suggestion approved #channel` $suggestion_approved_channel_id\n";
			//Messages
			$documentation = $documentation . "**Messages:**\n";
			$documentation = $documentation . "`species messageid` $species_message_id\n";
			$documentation = $documentation . "`species2 messageid` $species2_message_id\n";
			$documentation = $documentation . "`species3 messageid` $species3_message_id\n";
			$documentation = $documentation . "`sexuality messageid` $sexuality_message_id\n";
			$documentation = $documentation . "`gender messageid` $gender_message_id\n";
			$documentation = $documentation . "`customroles messageid` $customroles_message_id\n";
			
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
		if (substr($message_content_lower, 0, 22) == $command_symbol . 'setup verify channel '){
			$filter = "$command_symbol" . "setup verify channel ";
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
		if (substr($message_content_lower, 0, 17) == $command_symbol . 'setup verifylog '){
			$filter = "$command_symbol" . "setup verifylog ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = str_replace("<#", "", $value);
			$value = str_replace(">", "", $value);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "verifylog_channel_id.php", $value);
				$message->reply("Verifylog channel ID saved!");
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
		if (substr($message_content_lower, 0, 26) == $command_symbol . 'setup suggestion pending '){
			$filter = "$command_symbol" . "setup suggestion pending ";
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
		if (substr($message_content_lower, 0, 27) == $command_symbol . 'setup suggestion approved '){
			$filter = "$command_symbol" . "setup suggestion approved ";
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
		if (substr($message_content_lower, 0, 16) == $command_symbol . 'setup species3 '){
			$filter = "$command_symbol" . "setup species3 ";
			$value = str_replace($filter, "", $message_content_lower);
			$value = trim($value);
			if(is_numeric($value)){
				VarSave($guild_folder, "species3_message_id.php", $value);
				$message->reply("Species3 message ID saved!");
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
		
		if( ($rolepicker_id != "") && ($rolepicker_id != NULL) ){ //Message rolepicker menus
				GLOBAL $species, $species2, $species3, $species_message_text, $species2_message_text, $species3_message_text;
				GLOBAL $sexualities, $sexuality_message_text;
				GLOBAL $gender, $gender_message_text;
				GLOBAL $customroles, $customroles_message_text;
				
				if ($creator || $owner)
				if ($message_content_lower == $command_symbol . 'message species'){ //;message species
					VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
					$author_channel->send($species_message_text)->then(function($message) use ($guild_folder, $species){;
						VarSave($guild_folder, "species_message_id.php", strval($message->id));
						foreach($species as $var_name => $value){
							$message->react($value);
						}
						return true;
					});
					return true;
				}
				
				if ($creator || $owner)
				if ($message_content_lower == $command_symbol . 'message species2'){ //;message species2
					VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
					$author_channel->send($species2_message_text)->then(function($message) use ($guild_folder, $species2){;
						VarSave($guild_folder, "species2_message_id.php", strval($message->id));
						foreach($species2 as $var_name => $value){
							$message->react($value);
						}
						return true;
					});
					return true;
				}
				
				if ($creator || $owner)
				if ($message_content_lower == $command_symbol . 'message species3'){ //;message species3
					VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
					$author_channel->send($species3_message_text)->then(function($message) use ($guild_folder, $species3){;
						VarSave($guild_folder, "species3_message_id.php", strval($message->id));
						foreach($species3 as $var_name => $value){
							$message->react($value);
						}
						return true;
					});
					return true;
				}
				
				if ($creator || $owner)
				if ( ($message_content_lower == $command_symbol . 'message sexuality') || ($message_content_lower == $command_symbol . 'message sexualities') ) { //;message sexual
					VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
					$author_channel->send($sexuality_message_text)->then(function($message) use ($guild_folder, $sexualities){;
						VarSave($guild_folder, "sexuality_message_id.php", strval($message->id));
						foreach($sexualities as $var_name => $value){
							$message->react($value);
						}
						return true;
					});
					return true;
				}
				
				if ($creator || $owner)
				if ($message_content_lower == $command_symbol . 'message gender'){ //;message gender
					VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
					$author_channel->send($gender_message_text)->then(function($message) use ($guild_folder, $gender){;
						VarSave($guild_folder, "gender_message_id.php", strval($message->id));
						foreach($gender as $var_name => $value){
							$message->react($value);
						}
						return true;
					});
					return true;
				}
				
				if ($creator || $owner)
				if ($message_content_lower == $command_symbol . 'message customroles'){ //;message customroles
					VarSave($guild_folder, "rolepicker_channel_id.php", strval($author_channel_id)); //Make this channel the rolepicker channel
					$author_channel->send($customroles_message_text)->then(function($message) use ($guild_folder, $customroles){;
						VarSave($guild_folder, "customroles_message_id.php", strval($message->id));
						foreach($customroles as $var_name => $value){
							$message->react($value);
						}
						return true;
					});
					return true;
				}
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
				/*
				//species2
				$documentation = $documentation . "`species2`\n";
				//species3
				$documentation = $documentation . "`species3`\n";
				*/
				//sexuality
				$documentation = $documentation . "`sexuality`\n";
				//gender
				$documentation = $documentation . "`gender`\n";
				//customrole
				$documentation = $documentation . "`customrole`\n";
				
				
				//TODO:
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
				//ban
				$documentation = $documentation . "`suggest approve #`\n";
				//ban
				$documentation = $documentation . "`suggest deny #`\n";
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
				//cooldown
				$documentation = $documentation . "`cooldown` or `cd` tells you how much time you must wait before using another Vanity command \n";
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
				//bap
				$documentation = $documentation . "`pet`\n";
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
			//suggest
			if($suggestion_pending_channel)
			$documentation = $documentation . "`suggest`\n";
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
		
		//if ($suggestion_approved_channel_id)
		if ($creator || $owner || $mod || $admin || $dev)
		if ( (substr($message_content_lower, 0, 20) == $command_symbol . 'suggestion approve ') || (substr($message_content_lower, 0, 17) == $command_symbol . 'suggest approve ') ) { //;suggestion
			$filter = "$command_symbol" . "suggestion approve ";
			$value = str_replace($filter, "", $message_content_lower);
			$filter = "$command_symbol" . "suggest approve ";
			$value = str_replace($filter, "", $value);
			if( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter an integer number");
			if(is_numeric($value)){
				//Get the message stored at the index
				$array = VarLoad($guild_folder, "guild_suggestions.php");
				if( ($array[$value]) && ($array[$value] != "Approved" ) && ($array[$value] != "Denied" ) ){
					$embed = $array[$value];
					//Repost the suggestion
					$suggestion_approved_channel->send('', array('embed' => $embed))->then(function($message) use ($guild_folder, $embed){
						$message->react("👍");
						$message->react("👎");
					});
					//Clear the value stored in the array
					$array[$value] = "Approved";
					return $message->react("👍");
				}else return $message->reply("Suggestion not found or already processed!");
			}else return $message->reply("Invalid input! Please enter an integer number");
			return true; //catch
		}
		
		if ($creator || $owner || $mod || $admin || $dev)
		if ( (substr($message_content_lower, 0, 17) == $command_symbol . 'suggestion deny ') || (substr($message_content_lower, 0, 14) == $command_symbol . 'suggest deny ') ) { //;suggestion
			$filter = "$command_symbol" . "suggestion deny ";
			$value = str_replace($filter, "", $message_content_lower);
			$filter = "$command_symbol" . "suggest deny ";
			$value = str_replace($filter, "", $value);
			if( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter an integer number");
			if(is_numeric($value)){
				//Get the message stored at the index
				$array = VarLoad($guild_folder, "guild_suggestions.php");
				if( ($array[$value]) && ($array[$value] != "Approved" ) && ($array[$value] != "Denied" ) ){
					$embed = $array[$value];
					//Clear the value stored in the array
					$array[$value] = "Denied";
					return $message->react("👍");
				}else return $message->reply("Suggestion not found or already processed!");
			}else return $message->reply("Invalid input! Please enter an integer number");
			return true; //catch
		}
		
		if ($suggestion_pending_channel)
		if ( (substr($message_content_lower, 0, 12) == $command_symbol . 'suggestion ') || (substr($message_content_lower, 0, 9) == $command_symbol . 'suggest ') ){ //;suggestion
			$filter = "$command_symbol" . "suggestion ";
			$value = str_replace($filter, "", $message_content_lower);
			$filter = "$command_symbol" . "suggest ";
			$value = str_replace($filter, "", $value);
			if ( ($value == "") || ($value == NULL) ) return $message->reply("Invalid input! Please enter text for your suggestion");
				//Build the embed message
				$message_sanitized = str_replace("*","",$message_content_lower);
				$message_sanitized = str_replace("_","",$message_sanitized);
				$message_sanitized = str_replace("`","",$message_sanitized);
				$message_sanitized = str_replace("\n","",$message_sanitized);
				$doc_length = strlen($message_sanitized);
				if ($doc_length < 1025){
					//Find the size of $suggestions and get what will be the next number
					$array = VarLoad($guild_folder, "guild_suggestions.php");
					$array_count = sizeof($array);
					//Build the embed
					$embed = new \CharlotteDunois\Yasmin\Models\MessageEmbed();
					$embed
						->setTitle("#$array_count")																	// Set a title
						->setColor("a7c5fd")																	// Set a color (the thing on the left side)
						->setDescription("$value")																// Set a description (below title, above fields)
	//					->addField("⠀", "$reason")																// New line after this
						
	//					->setThumbnail("$author_avatar")														// Set a thumbnail (the image in the top right corner)
	//					->setImage('https://avatars1.githubusercontent.com/u/4529744?s=460&v=4')             	// Set an image (below everything except footer)
						->setTimestamp()                                                                     	// Set a timestamp (gets shown next to footer)
						->setAuthor("$author_check ($author_id)", "$author_avatar")  									// Set an author with icon
						->setFooter("Palace Bot by Valithor#5947")                             					// Set a footer without icon
						->setURL("");                             												// Set the URL
	//				Post embedded suggestion to suggestion_pending_channel
					$suggestion_pending_channel->send('', array('embed' => $embed))->then(function($message) use ($guild_folder, $embed){
						$message->react("👍");
						$message->react("👎");
						//Save the suggestion somewhere
						$array = VarLoad($guild_folder, "guild_suggestions.php");
						$array[] = $embed;
						VarSave($guild_folder, "guild_suggestions.php", $array);
					});
				}else{
					$message->reply("Please shorten your suggestion!");
				}
				$message->delete();
				return true;
				
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
			if(!CheckFile($author_folder, "peter_count.php"))		$peter_count		= 0;													
			else 													$peter_count		= VarLoad($author_folder, "peter_count.php");			

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
			if(!CheckFile($author_folder, "peted_count.php"))		$peted_count		= 0;													
			else 													$peted_count		= VarLoad($author_folder, "peted_count.php");				
			
			if ( ($message_content_lower == $command_symbol . 'cooldown') || ($message_content_lower == $command_symbol . 'cd') ){//;cooldown ;cd
				echo "COOLDOWN CHECK" . PHP_EOL;
//				Check Cooldown Timer
				$cooldown = CheckCooldown($author_folder, "vanity_time.php", $avatar_limit);
				if ( ($cooldown[0] == true) || ($bypass) ){
					return $message->reply("No cooldown.");
				}else{
//					Reply with remaining time
					$waittime = $avatar_limit_seconds - $cooldown[1];
					$formattime = FormatTime($waittime);
					return $message->reply("You must wait $formattime before using this command again.");
				}
			}
			
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
			
			if (substr($message_content_lower, 0, 5) == $command_symbol . 'pet ' ){ //;pet @
				echo "PET" . PHP_EOL;
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
							$pet_messages								= array();
							$pet_messages[]								= "<@$author_id> pets <@$mention_id>";
							$index_selection							= GetRandomArrayIndex($pet_messages);
//							echo "random pet_messages: " . $pet_messages[$index_selection];
//							Send the message
							$author_channel->send($pet_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$peter_count++;
							VarSave($author_folder, "peter_count.php", $peter_count);
							//Load target get statistics
							if(!CheckFile($guild_folder."/".$mention_id, "vanity_get_count.php"))	$vanity_get_count	= 0;
							else 																	$vanity_get_count 	= VarLoad($guild_folder."/".$mention_id, "vanity_get_count.php");
							if(!CheckFile($guild_folder."/".$mention_id, "peted_count.php"))		$peted_count		= 0;
							else 																	$peted_count 		= VarLoad($guild_folder."/".$mention_id, "peted_count.php");
							//Increment get stat counter of target
							$vanity_get_count++;
							VarSave($guild_folder."/".$mention_id, "vanity_get_count.php", $vanity_get_count);
							$peted_count++;
							VarSave($guild_folder."/".$mention_id, "peted_count.php", $peted_count);
//							Set Cooldown
							SetCooldown($author_folder, "vanity_time.php");
							return true; //No more processing, we only want to process the first person mentioned
						}else{
							$self_pet_messages							= array();
							$self_pet_messages[]						= "<@$author_id> placed a paw on their own nose. How silly!";
							$index_selection							= GetRandomArrayIndex($self_pet_messages);
//							Send the mssage
							$author_channel->send($self_pet_messages[$index_selection]);
							//Increment give stat counter of author
							$vanity_give_count++;
							VarSave($author_folder, "vanity_give_count.php", $vanity_give_count);
							$peter_count++;
							VarSave($author_folder, "peter_count.php", $peter_count);
							//Increment get stat counter of author
							$vanity_get_count++;
							VarSave($author_folder, "vanity_get_count.php", $vanity_get_count);
							$peted_count++;
							VarSave($author_folder, "peted_count.php", $peted_count);
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
						->addField("Pets", 				"$peter_count", true)
						->addField("⠀", 				"⠀", true)												// Invisible unicode for separator
						->addField("Total Received", 	"$vanity_get_count")									// New line after this
						->addField("Hugs", 				"$hugged_count", true)
						->addField("Kisses", 			"$kissed_count", true)
						->addField("Nuzzles", 			"$nuzzled_count", true)
						->addField("Boops", 			"$booped_count", true)
						->addField("Baps", 				"$baped_count", true)
						->addField("Pets", 				"$peted_count", true)
						
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
//			$verifylog_channel																					//TextChannel				//echo "channel_messages class: " . get_class($verifylog_channel) . PHP_EOL;
//			$author_messages = $verifylog_channel->fetchMessages(); 											//Promise
//			echo "author_messages class: " . get_class($author_messages) . PHP_EOL; 						//Promise
			$verifylog_channel->fetchMessages()->then(function($message_collection) use ($verifylog_channel){	//Resolve the promise
//				$verifylog_channel and the new $message_collection can be used here
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
		if ( (substr($message_content_lower, 0, 3) == $command_symbol . 'v ') || (substr
