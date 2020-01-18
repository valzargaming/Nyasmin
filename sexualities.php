<?php
$sexuality_message_id_default = "";
if(CheckFile(null, "sexuality_message_id.php"))	$sexuality_message_id = VarLoad(null, "sexuality_message_id.php");			//Load saved option file
else{
	sexuality_message_id = $sexuality_message_id_default;
	VarSave(null, "sexuality_message_id.php", $sexuality_message_id);
}

$straight = "👩‍❤️‍💋‍👨"; 
$questioning = "❓";
$asexual = "🛑";
$pansexual = "🥘";
$bicurious = "❔";
$bi = "🚻";
$gay = "🏳️‍🌈";

//Message copy-pasta:
$sexuality_message_text: = "**Role Menu: Sexualities**
:kiss_woman_man: : `Straight`
:question: : `Questioning Sexuality`
:octagonal_sign: : `Asexual`
:shallow_pan_of_food: : `Pansexual`
:grey_question: : `Bicurious`
:two: : `Bi`
:rainbow_flag: : `Gay/Lesbian`
";
?>
