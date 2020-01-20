<?php
//These are static variables should be set when the bot is first added to the server.
//Every value should be filled out except where otherwise noted
//This config file does not include options that can be enabled/disabled with chat commands

$guild_id					= "609814936721424400";		//(Not yet implemented)
$bot_id						= "662093882795753482";		//id of this bot

$rolepicker_id				= "395098628579917825";		//id of the user that posted the role picker messages
$species_message_id			= "668203989749465088"; //id of the Species Menu message
$sexuality_message_id		= "668206815653134367"; //id of the Sexualities Menu message
$gender_message_id			= "668206840173297685"; //id of the Gender Menu message

$rolepicker_option = true;
$species_option = true;
$sexuality_option = true;
$gender_option = true;

$welcome_channel_id			= "662042030557364224";		//Channel where a detailed message about the user gets posted
$welcome_public_channel_id	= "659816172212191262";		//Simple welcome message tagging users
$introduction_channel_id	= "609814936721424406";		//Usually #introductions or #general (Not currently implemented)
$modlog_channel_id			= "659804362297573396";		//Log stuff here
$verifylog_channel_id		= "662858877343236096";		//Log verifications (Not currently implemented)
$getverifed_channel_id		= "662070861960052767";		//Where users should be requesting server verification
//$watch_channel_id			= "662044237411647550";		//Someone being watched has their messages duplicated to this channel instead of a DM (Leave commented to use DMs)

$role_18_id			= "";						//Leave blank if 18+ commands are not being used
$role_verified_id	= "659801869396213773";		//Verified role that gives people access to channels


$role_dev_id	= "662076754042683413";				//Developer role (overrides certain restrictions)
$role_owner_id	= "609816345961431041";				//Owner of the guild
$role_admin_id	= "609910254011940864";				//Admins
$role_mod_id	= "662837604483465276";				//Moderators
$role_bot_id	= "609830821792055296";				//Bots
$role_vzgbot_id	= "662095729984143370";				//Palace Bot: THIS ROLE MUST HAVE ADMINISTRATOR PRIVILEGES!
?>
