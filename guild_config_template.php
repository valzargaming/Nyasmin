<?php
$command_symbol				= ";"; //Must be prefixed to messages for commands (This should never be blank!)
$welcome_public_channel_id	= "";						//Simple welcome message for new users

$welcome_channel_id			= "";		//Channel where a detailed message about the user gets posted
$introduction_channel_id	= "";						//Usually #introductions or #general (Not currently implemented)
$modlog_channel_id			= "";		//Log stuff here
$verifylog_channel_id		= "";						//Log verifications (Not currently implemented)
$getverified_channel_id		= "";						//Where users should be requesting server verification
//$watch_channel_id			= "";						//Someone being watched has their messages duplicated to this channel instead of a DM (Leave commented to use DMs)

$role_18_id			= "";								//Leave blank if 18+ commands are not being used
$role_verified_id	= "";								//Verified role that gives people access to channels

$role_dev_id	= "";								//Developer role (overrides certain restrictions)
$role_owner_id	= "";				//Owner of the guild
$role_admin_id	= "";				//Admins
$role_mod_id	= "";				//Moderators
$role_bot_id	= "";				//Bots
$role_vzgbot_id	= "";				//Palace Bot: THIS ROLE MUST HAVE ADMINISTRATOR PRIVILEGES!
$role_muted_id	= "";				//This role should not be allowed access any channels

$rolepicker_id				= "";		//id of the user that posted the role picker messages
$species_message_id			= "";		//id of the Species Menu message
$sexuality_message_id		= "";		//id of the Sexualities Menu message
$gender_message_id			= "";		//id of the Gender Menu message
//You can add your own custom roles too! Locate the Discord emoji on https://emojipedia.org/discord/ and use it as the unicode in custom_roles.php
$customroles_message_id		= "";		//Replace this number with the message ID of your custom roles message
?>
