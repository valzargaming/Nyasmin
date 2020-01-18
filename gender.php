<?php
$gender_message_id_default = "";
if(CheckFile(null, "gender_message_id.php"))	$gender_message_id = VarLoad(null, "gender_message_id.php");			//Load saved option file
else{
	gender_message_id = $gender_message_id_default;
	VarSave(null, "gender_message_id.php", $gender_message_id);
}

$fluid = "💧";
$nonbinary = "⛔";
$female = "♀️";
$male = "♂️";

//Message copy-pasta:
$gender_message_text: = "**Role Menu: Gender**
:droplet: : `Gender Fluid`
:no_entry: : `Non Binary`
:female_sign: : `Female`
:male_sign: : `Male`
";
?>
