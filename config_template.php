<?php
//These are static variables should be set when the bot is first added to the server.
//Every value should be filled out except where otherwise noted
//This config file does not include options that can be enabled/disabled with chat commands

$welecome_channel_id = "";		//Welcome messages tagging users
$introduction_channel_id = "";	//Usually #introductions or #general
$modlog_channel_id = "";			//Log stuff here
$verifylog_channel_id = "";		//Log verifications
$verify_channel_id = "";			//Cleared when all requesting users have been verified
//$watch_channel_id = "";			//Someone being watched has their messages duplicated to this channel instead of a DM (Leave commented to use DMs)

$role_18_id = "";									//Leave blank if 18+ commands are not being used
$role_verified_id = "";			//Verified role that gives people access to channels


$role_dev_id = "";				//Developer role overrides certain restrictions
$role_owner_id = "";				//Owner of the guild
$role_admin_id = "";				//Admins
$role_mod_id = "";				//Moderators
$role_bot_id = "";				//Bots
$role_vzgbot_id = "";				//Palace Bot: THIS ROLE MUST HAVE ADMINISTRATOR PRIVILEGES!
?>
