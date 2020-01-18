<?php
//These are static variables should be set when the bot is first added to the server.
//Every value should be filled out except where otherwise noted
//This config file does not include options that can be enabled/disabled with chat commands

$welcome_channel_id			    = "";		//Channel where a detailed message about the user gets posted
$welcome_public_channel_id	= "";		//Simple welcome message tagging users
$introduction_channel_id  	= "";		//Usually #introductions or #general (Not currently implemented)
$modlog_channel_id			    = "";		//Log stuff here
$verifylog_channel_id		    = "";		//Log verifications (Not currently implemented)
$getverifed_channel_id	  	= "";		//Where users should be requesting server verification
//$watch_channel_id			    = "";		//Someone being watched has their messages duplicated to this channel instead of a DM (Leave commented to use DMs)

$role_18_id			            = "";		//Leave blank if 18+ commands are not being used
$role_verified_id	          = "";		//Verified role that gives people access to channels


$role_dev_id	              = "";		//Developer role (overrides certain restrictions)
$role_owner_id	            = "";		//Owner of the guild
$role_admin_id	            = "";		//Admins
$role_mod_id	              = "";		//Moderators
$role_bot_id	              = "";		//Bots
$role_vzgbot_id             = "";		//Palace Bot: THIS ROLE MUST HAVE ADMINISTRATOR PRIVILEGES!
?>
