<?php
echo "----- BOT DISCONNECTED FROM DISCORD WITH CODE $code FOR REASON: $erMsg -----" . PHP_EOL;
echo "RESTARTING BOT" . PHP_EOL;
$restart_cmd = 'cmd /c "'. __DIR__  . '\run.bat"'; //echo $restart_cmd . PHP_EOL;
system($restart_cmd);
//die;
?>