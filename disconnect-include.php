<?php
echo "----- BOT DISCONNECTED FROM DISCORD WITH CODE $code FOR REASON: $erMsg -----" . PHP_EOL;
//echo "RESTARTING BOT" . PHP_EOL;
//$restart_cmd = 'cmd /c "'. __DIR__  . '\run.bat"'; //echo $restart_cmd . PHP_EOL;
//system($restart_cmd);


echo "[RESTART LOOP]" . PHP_EOL;
$dt = new DateTime("now");  // convert UNIX timestamp to PHP DateTime
echo "[TIME] " . $dt->format('d-m-Y H:i:s') . PHP_EOL; // output = 2017-01-01 00:00:00
//$loop->stop();
$discord->destroy();
$discord = new \CharlotteDunois\Yasmin\Client(array(), $loop);
$discord->login($token)->done(null, function ($error){
	echo "[LOGIN ERROR] $error".PHP_EOL; //Echo any errors
});
//$loop->run();

?>
