<?php
//This file and the variables it loads get reacquired every time they're needed, allowing for persistence
//Changes made do not require for the bot to be restarted

//Command Symbol
if(!CheckFile($author_guild_id, "command_symbol.php"))
	$command_symbol	= ";";	//Channel where a detailed message about the user gets posted
	VarSave($author_guild_id, "command_symbol.php", $command_symbol);
else $command_symbol	= VarLoad($author_guild_id, "command_symbol.php");

//Channel IDs
if(!CheckFile($author_guild_id, "welcome_channel_id.php"))
	$welcome_channel_id	= "";	//Channel where a detailed message about the user gets posted
	VarSave($author_guild_id, "welcome_channel_id.php", $welcome_channel_id);
else $welcome_channel_id	= VarLoad($author_guild_id, "welcome_channel_id.php");

if(!CheckFile($author_guild_id, "welcome_public_channel_id.php"))
	$welcome_public_channel_id	= "";	//Simple welcome message tagging users
	VarSave($author_guild_id, "welcome_public_channel_id.php", $welcome_public_channel_id);
else $welcome_public_channel_id	= VarLoad($author_guild_id, "welcome_public_channel_id.php");

if(!CheckFile($author_guild_id, "introduction_channel_id.php"))
	$introduction_channel_id	= "";	//Usually #introductions or #general (Not currently implemented)
	VarSave($author_guild_id, "introduction_channel_id.php", $introduction_channel_id);
else $introduction_channel_id	= VarLoad($author_guild_id, "introduction_channel_id.php");

if(!CheckFile($author_guild_id, "modlog_channel_id.php"))
	$modlog_channel_id	= "";	//Usually #introductions or #general (Not currently implemented)
	VarSave($author_guild_id, "modlog_channel_id.php", $modlog_channel_id); //Log stuff here
else $modlog_channel_id	= VarLoad($author_guild_id, "modlog_channel_id.php");

if(!CheckFile($author_guild_id, "verifylog_channel_id.php"))
	$verifylog_channel_id	= "";	//Log verifications (Not currently implemented)
	VarSave($author_guild_id, "verifylog_channel_id.php", $verifylog_channel_id); //Log stuff here
else $verifylog_channel_id	= VarLoad($author_guild_id, "verifylog_channel_id.php");

if(!CheckFile($author_guild_id, "getverified_channel_id.php"))
	$getverified_channel_id	= "";	//Where users should be requesting server verification
	VarSave($author_guild_id, "getverified_channel_id.php", $getverified_channel_id); //Log stuff here
else $getverified_channel_id	= VarLoad($author_guild_id, "getverified_channel_id.php");

/*
if(!CheckFile($author_guild_id, "watch_channel_id.php"))
	$watch_channel_id	= "";	//Someone being watched has their messages duplicated to this channel instead of a DM (Leave commented to use DMs)
	VarSave($watch_channel_id, "watch_channel_id.php", $watch_channel_id); //Log stuff here
else $watch_channel_id	= VarLoad($author_guild_id, "watch_channel_id.php");
*/

//Optional Role IDs
if(!CheckFile($author_guild_id, "role_18_id.php"))
	$role_18_id	= "";	//Someone being watched has their messages duplicated to this channel instead of a DM (Leave commented to use DMs)
	VarSave($author_guild_id, "role_18_id.php", $role_18_id); //Leave blank if 18+ commands are not being used
else $role_18_id	= VarLoad($author_guild_id, "role_18_id.php");

if(!CheckFile($author_guild_id, "role_verified_id.php"))
	$role_verified_id	= "";	//Verified role that gives people access to channels
	VarSave($author_guild_id, "role_verified_id.php", $role_verified_id); //Leave blank if 18+ commands are not being used
else $role_verified_id	= VarLoad($author_guild_id, "role_verified_id.php");

//Required Role IDs
if(!CheckFile($author_guild_id, "role_dev_id.php"))
	$role_dev_id	= "";	//Developer role (overrides certain restrictions)
	VarSave($author_guild_id, "role_dev_id.php", $role_dev_id); //Leave blank if 18+ commands are not being used
else $role_dev_id	= VarLoad($author_guild_id, "role_dev_id.php");

if(!CheckFile($author_guild_id, "role_owner_id.php"))
	$role_owner_id	= "";	//Owner of the guild
	VarSave($author_guild_id, "role_owner_id.php", $role_owner_id); //Leave blank if 18+ commands are not being used
else $role_owner_id	= VarLoad($author_guild_id, "role_owner_id.php");

if(!CheckFile($author_guild_id, "role_admin_id.php"))
	$role_admin_id	= "";	//Admins
	VarSave($author_guild_id, "role_admin_id.php", $role_admin_id); //Leave blank if 18+ commands are not being used
else $role_admin_id	= VarLoad($author_guild_id, "role_admin_id.php");

if(!CheckFile($author_guild_id, "role_mod_id.php"))
	$role_mod_id	= "";	//Moderators
	VarSave($author_guild_id, "role_mod_id.php", $role_mod_id); //Leave blank if 18+ commands are not being used
else $role_mod_id	= VarLoad($author_guild_id, "role_mod_id.php");

if(!CheckFile($author_guild_id, "role_bot_id.php"))
	$role_bot_id	= "";	//Bots
	VarSave($author_guild_id, "role_bot_id.php", $role_bot_id); //Leave blank if 18+ commands are not being used
else $role_bot_id	= VarLoad($author_guild_id, "role_bot_id.php");

if(!CheckFile($author_guild_id, "role_vzgbot_id.php"))
	$role_vzgbot_id	= "";	//Palace Bot: THIS ROLE MUST HAVE ADMINISTRATOR PRIVILEGES!
	VarSave($author_guild_id, "role_vzgbot_id.php", $role_vzgbot_id); //Leave blank if 18+ commands are not being used
else $role_vzgbot_id	= VarLoad($author_guild_id, "role_vzgbot_id.php");

if(!CheckFile($author_guild_id, "role_muted_id.php"))
	$role_muted_id	= "";	//This role should not be allowed access any channels
	VarSave($author_guild_id, "role_muted_id.php", $role_muted_id); //Leave blank if 18+ commands are not being used
else $role_muted_id	= VarLoad($author_guild_id, "role_muted_id.php");

//Rolepicker user ID
if(!CheckFile($author_guild_id, "rolepicker_id.php"))
	$rolepicker_id	= "";	//id of the user that posted the role picker messages
	VarSave($author_guild_id, "rolepicker_id.php", $rolepicker_id); //Leave blank if 18+ commands are not being used
else $rolepicker_id	= VarLoad($author_guild_id, "rolepicker_id.php");
//Rolepicker message IDs
if(!CheckFile($author_guild_id, "species_message_id.php"))
	$species_message_id	= "";	//id of the Species Menu message
	VarSave($author_guild_id, "species_message_id.php", $species_message_id); //Leave blank if 18+ commands are not being used
else $species_message_id	= VarLoad($author_guild_id, "species_message_id.php");

if(!CheckFile($author_guild_id, "sexuality_message_id.php"))
	$sexuality_message_id	= "";	//id of the Sexualities Menu message
	VarSave($author_guild_id, "sexuality_message_id.php", $sexuality_message_id); //Leave blank if 18+ commands are not being used
else $sexuality_message_id	= VarLoad($author_guild_id, "sexuality_message_id.php");

if(!CheckFile($author_guild_id, "gender_message_id.php"))
	$gender_message_id	= "";	//id of the Gender Menu message
	VarSave($author_guild_id, "gender_message_id.php", $gender_message_id); //Leave blank if 18+ commands are not being used
else $gender_message_id	= VarLoad($author_guild_id, "gender_message_id.php");

//You can add your own custom roles too! Locate the Discord emoji on https://emojipedia.org/discord/ and use it as the unicode in custom_roles.php
if(!CheckFile($author_guild_id, "customroles_message_id.php"))
	$customroles_message_id	= "";	//id of the Gender Menu message
	VarSave($author_guild_id, "customroles_message_id.php", $customroles_message_id); //Leave blank if 18+ commands are not being used
else $customroles_message_id	= VarLoad($author_guild_id, "customroles_message_id.php");
?>
