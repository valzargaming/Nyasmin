<?php
include_once "custom_functions.php";
$author_guild = $member_new->guild;
$author_guild_id = $member_new->guild->id;
echo "guildMemberUpdate ($author_guild_id)" . PHP_EOL;

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

$member_id			= $member_new->id;
$member_guild		= $member_new->guild;
$new_user			= $member_new->user;
$old_user			= $member_old->user;

$user_folder		= "users/$member_id";
CheckDir($user_folder);

$guild_folder = "\\guilds\\$author_guild_id";
if(!CheckDir($guild_folder)){
	if(!CheckFile($guild_folder, "guild_owner_id.php")){
		VarSave($guild_folder, "guild_owner_id.php", $guild_owner_id);
	}else $guild_owner_id	= VarLoad($guild_folder, "guild_owner_id.php");
}

//Load config variables for the guild
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
if(!include "$guild_config_path"){
	echo "CONFIG CATCH!" . PHP_EOL;
	$counter = VarLoad($guild_folder, "config_retry.php");
	if ($counter >= 10){
		$counter++;
		VarSave($guild_folder, "config_retry.php", $config_retry);
	}else{
		$author_guild->leave($author_guild_id)->done(null, function ($error){
			echo $error.PHP_EOL; //Echo any errors
		});
		rmdir(__DIR__  . $guild_folder);
		echo "GUILD DIR REMOVED" . PHP_EOL;
	}
}

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
	if ($old_displayName && $array)
	if (!in_array($old_displayName, $array))
		$array[] = $old_displayName; 
	if ($new_displayName && $array)
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
?>