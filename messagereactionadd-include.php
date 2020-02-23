<?php
$me = $reaction->me;
if ($me === true){ //Don't process reactions this bot makes
	echo "MESSAGE REACTION ADDED" . PHP_EOL;
	return true;
}
echo "messageReactionAdd" . PHP_EOL;

//Load message info
$message					= $reaction->message;
$message_content			= $message->content;

//Load guild info
$guild						= $message->guild;
$author_guild				= $message->guild;
$author_guild_id			= $author_guild->id;

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
$respondent_check 			= "$respondent_username#$respondent_discriminator"; 				echo "respondent_check: " . $respondent_check . PHP_EOL;
$respondent_member 			= $author_guild->members->get($respondent_id);

if ( ($message_content == NULL) || ($message_content == "") ) return true; //Don't process blank messages, bots, webhooks, or rich embeds
$message_content_lower = strtolower($message_content);

//Create a folder for the guild if it doesn't exist already
$guild_folder = "\\guilds\\$author_guild_id";
CheckDir($guild_folder);
//Load config variables for the guild
$guild_config_path = __DIR__  . "$guild_folder\\guild_config.php"; //echo "guild_config_path: " . $guild_config_path . PHP_EOL;
include "$guild_config_path";

//Role picker stuff
$message_id					= $message->id;														//echo "message_id: " . $message_id . PHP_EOL;
GLOBAL $species, $species2, $species3, $sexualities, $gender, $customroles;

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
				foreach ($species2 as $var_name => $value){
					//$message->react($value);
				}
				
			}
			break;
		case ($species3_message_id):
			if($rp1){
				echo "species3 reaction" . PHP_EOL;
				foreach ($species3 as $var_name => $value){
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
				foreach ($species3 as $var_name => $value){
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
		case ($customroles_message_id):
			if($rp4){
				echo "Custom roles reaction" . PHP_EOL;
				foreach ($customroles as $var_name => $value){
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
				foreach ($customroles as $var_name => $value){
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
?>
