<?php
echo "----- BOT DISCONNECTED FROM DISCORD WITH CODE $code FOR REASON: $erMsg -----" . PHP_EOL;

if ($code == "4004"){
	echo "[CRITICAL] TOKEN INVALIDATED BY DISCORD!" . PHP_EOL;
	die();
	return true;
}

$discord->destroy();
if ( ($vm == true) && ( ($code == "1000") || ($code == "4000") || ($code == "4003") ) ){
	$loop->stop();
	$loop = \React\EventLoop\Factory::create(); //Recreate loop if the cause of the disconnect was possibly related to a VM being paused
}
$discord = new \CharlotteDunois\Yasmin\Client(array(), $loop); //Create a new client using the same React loop

echo "[RESTART LOOP]" . PHP_EOL;
$dt = new DateTime("now");  // convert UNIX timestamp to PHP DateTime
echo "[TIME] " . $dt->format('d-m-Y H:i:s') . PHP_EOL; // output = 2017-01-01 00:00:00

$discord->login($token)->done(null, function ($error){
	echo "[LOGIN ERROR] $error".PHP_EOL; //Echo any errors
});

if ( ($vm == true) && ( ($code == "1000") || ($code == "4000") || ($code == "4003") ) ){
	$loop->run();
}
?>
