<?php
//guild_config.php needs to be located in a folder that shares the guild ID
//The folder will be automatically created after the bot sees a message being sent in the server for the first time, but you can create it yourself
//Make a copy of guild_config_template.php and place it in the folder
//**ALL** field in guild_config.php **MUST** be filled out unless otherwise noted or the bot will probably experience crashes

//This config file includes options that are enabled/disabled with chat commands
//Any changes made to this file will require a full restart of the bot before they take place

//Variables in this file are initialized at the global scope when the bot is started
//Variables need to be reinitialized within the event listener by using the GLOBAL keyword (e.g. GLOBAL $bot_id;)

//$server_invite = "https://discord.gg/vCrewVb"; //Invite link to the server when the bot is sent a DM (comment this line to disable)	
$bot_id	= "662093882795753482";	//id of this bot (change it to match your own)

//These are default options that should be set up before the bot is started for the first time. Any future changes need to be done with a chat command
$react_option = true;
$vanity_option = false;
$nsfw_option = false;
$rolepicker_option = false;
$species_option = false;
$sexuality_option = false;
$gender_option = false;
$custom_option = false; //Edit custom_roles.php before changing this to true!
?>
