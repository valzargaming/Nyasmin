<?php
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
?>