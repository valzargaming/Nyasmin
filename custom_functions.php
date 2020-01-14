<?php
//Returns a random result from an array
function GetRandomArrayIndex($array){
	if ( (count($array)) != 0 ){
		$array_size = count($array)-1;
		$index = rand(0, $array_size);
		return $index;
	} else return -1; //Not an array
}

//Removes a value from an array
function array_value_remove($value, $array){
	$remove_array = array($value);
	return array_diff($array, $remove_array);
}

//Checks if a directory contains any files
function is_dir_empty($dir) {
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if($fileInfo->isDot()) continue;
        return false;
    }
    return true;
}

//Checks if a folder exists and creates one if it doesn't
function CheckDir($foldername){
	//echo "CheckDir" . PHP_EOL;
	include("constants.php");
	$path = $DIR.$foldername."/";
	$exist = false;
	//Create folder if it doesn't already exist
	if (!file_exists($path)) {
		mkdir($path, 0777, true);
		echo "NEW DIR CREATED: $path" . PHP_EOL;
	}else $exist = true;
	return $exist;
}

//Checks if a file exists
function CheckFile($foldername, $filename){
	if ($foldername !== NULL) $folder_symbol = "/";
	//echo "CheckDir" . PHP_EOL;
	include("constants.php");
	$path = $DIR.$foldername.$folder_symbol.$filename;
	$exist = false;
	//Create folder if it doesn't already exist
	if (!file_exists($path)) {
		//echo "FILE NOT FOUND: $path" . PHP_EOL;
	}else $exist = true;
	return $exist;
}

//Saves a variable to a file
//Target is a full path, IE $DIR.target.php
function VarSave($foldername, $filename, $variable){
	if ($foldername !== NULL) $folder_symbol = "/";
	//echo "VarSave" . PHP_EOL;
	include("constants.php");
	$path = $DIR.$foldername.$folder_symbol; //echo "PATH: $path" . PHP_EOL;
	//Create folder if it doesn't already exist
	if (!file_exists($path)) {
		mkdir($path, 0777, true);
		echo "NEW DIR CREATED: $path" . PHP_EOL;
	}
	//Save variable to a file
	$serialized_variable = serialize($variable); 
	file_put_contents($path.$filename, $serialized_variable); //echo "FULL PATH: $path$filename" . PHP_EOL;
}

//Loads a variable from a file
//Target is a full path, IE $DIR.target.php
function VarLoad($foldername, $filename){
	if ($foldername !== NULL) $folder_symbol = "/";
	//echo "VarLoad" . PHP_EOL;
	include("constants.php");
	$path = $DIR.$foldername.$folder_symbol; //echo "PATH: $path" . PHP_EOL;
	//Make sure the file exists
	if (!file_exists($path.$filename))
		return null;
	//Load a variable from a file
	$loadedvar = file_get_contents($path.$filename); //echo "FULL PATH: $path$filename" . PHP_EOL;
	$unserialized = unserialize($loadedvar);
	return $unserialized;
}

function VarDelete($foldername, $filename){
	if ($foldername !== NULL) $folder_symbol = "/";
	echo "VarDelete" . PHP_EOL;
	include("constants.php");
	$path = $DIR.$foldername.$folder_symbol.$filename; //echo "PATH: $path" . PHP_EOL;
	//Make sure the file exists first
	if( CheckFile($foldername, $filename) ){
		//Delete the file
		unlink($path);
		clearstatcache();
	}else echo "NO FILE TO DELETE" . PHP_EOL;
}

/*
*********************
*********************
Timers and Cooldowns
*********************
*********************
*/

function TimeCompare($foldername, $filename){ //echo "foldername, filename: $foldername, $filename" . PHP_EOL;
	include("constants.php");
	$then = VarLoad($foldername, $filename); //instance of now;
	//echo "then: " . PHP_EOL; var_dump ($then) . PHP_EOL;
	//check if file exists
	if ($then){
		$sincetime = date_diff($now, $then);
		$timecompare['y'] = $sinceYear 		= $sincetime->y;
		$timecompare['m'] = $sinceMonth 	= $sincetime->m;
		$timecompare['d'] = $sinceDay 		= $sincetime->d;
		$timecompare['h'] = $sinceHour 		= $sincetime->h;
		$timecompare['i'] = $sinceMinute 	= $sincetime->i;
		$timecompare['s'] = $sinceSecond 	= $sincetime->s;
		echo 'Timer found to compare!' . PHP_EOL;
		return $timecompare;
	}else{
		//File not found, so return 0's
		$sincetime = date_diff($now, $now);
		$timecompare['y'] = $sinceYear 		= ($sincetime->y)+1; //Assume one year has passed, enough time to avoid any cooldown
		$timecompare['m'] = $sinceMonth 	= $sincetime->m;
		$timecompare['d'] = $sinceDay 		= $sincetime->d;
		$timecompare['h'] = $sinceHour 		= $sincetime->h;
		$timecompare['i'] = $sinceMinute 	= $sincetime->i;
		$timecompare['s'] = $sinceSecond 	= $sincetime->s;
		echo 'Timer not found to compare!' . PHP_EOL;
		//echo "timecompare: " . PHP_EOL; var_dump($timecompare) . PHP_EOL;
		return $timecompare;
	}
	//echo 'Timer not found to compare!' . PHP_EOL;
}

function TimeLimitCheck($time, $y, $m, $d, $h, $i, $s){
	//echo "time['s']: " . $time['s'] . PHP_EOL;
	if (!$time) return true; //Nothing to check, assume true
	if (!$y) $y = 0;//echo '$y: ' . $s . PHP_EOL;
	if (!$m) $m = 0;//echo '$m: ' . $s . PHP_EOL;
	if (!$d) $d = 0;//echo '$d: ' . $s . PHP_EOL;
	if (!$h) $h = 0;//echo '$h: ' . $s . PHP_EOL;
	if (!$i) $i = 0;//echo '$i: ' . $s . PHP_EOL;
	if (!$s) $s = 0;//echo '$s: ' . $s . PHP_EOL;
	//echo "time['y'] " . $time['y'] . PHP_EOL;
	//echo "time['m'] " . $time['m'] . PHP_EOL;
	//echo "time['d'] " . $time['d'] . PHP_EOL;
	//echo "time['h'] " . $time['h'] . PHP_EOL;
	//echo "time['i'] " . $time['i'] . PHP_EOL;
	//echo "time['s'] " . $time['s'] . PHP_EOL;
	//Calculate total number of seconds needed to continue.
	$required_time =
	($s) +
	($i * 60) +
	($h * 3600) +
	($d * 86400) +
	($m * 2629746) +
	($y * 31556952);
	//echo 'required_time: ' . $required_time . PHP_EOL;
	//Calculate total number of seconds passed.
	$passed_time =
	($time['s']) +
	($time['i'] * 60) +
	($time['h'] * 3600) +
	($time['d'] * 86400) +
	($time['m'] * 2629746) +
	($time['y'] * 31556952);
	//echo 'passed_time: ' . $passed_time . PHP_EOL;
	$return_array = array();
	if ($passed_time > $required_time) {
		$return_array[0] = true;
	}else{
		$return_array[0] = false;
	}
	$return_array[1] = $passed_time;
	return $return_array;	
}

function PassedTimeCheck($y, $m, $d, $h, $i, $s){
	if (!$y) $y = 0;//echo '$y: ' . $s . PHP_EOL;
	if (!$m) $m = 0;//echo '$m: ' . $s . PHP_EOL;
	if (!$d) $d = 0;//echo '$d: ' . $s . PHP_EOL;
	if (!$h) $h = 0;//echo '$h: ' . $s . PHP_EOL;
	if (!$i) $i = 0;//echo '$i: ' . $s . PHP_EOL;
	if (!$s) $s = 0;//echo '$s: ' . $s . PHP_EOL;
	//Calculate total number of seconds passed.
	$passed_time =
	($s) +
	($i * 60) +
	($h * 3600) +
	($d * 86400) +
	($m * 2629746) +
	($y * 31556952);
	//echo 'passed_time: ' . $passed_time . PHP_EOL;
	if ($passed_time != 0) return $passed_time;	
}

function CheckCooldown($foldername, $filename, $limit_array){
	echo "CHECK COOLDOWN" . PHP_EOL;
	//echo "limit_array: " . PHP_EOL; var_dump ($limit_array) . PHP_EOL;
	$TimeCompare = TimeCompare($foldername, $filename);
	//echo "TimeCompare: " . PHP_EOL; var_dump ($TimeCompare) . PHP_EOL;
	//$timetopass = $timelimitcheck[0]; //True/False, whether enough time has passed
	//$timetopass = $timelimitcheck[1]; //total # of seconds
	if ($TimeCompare){
		$TimeLimitCheck = TimeLimitCheck($TimeCompare, $limit_array['year'], $limit_array['month'], $limit_array['day'], $limit_array['hour'], $limit_array['min'], $limit_array['sec']);
		//echo "TimeLimitCheck: " . PHP_EOL; var_dump ($TimeLimitCheck) . PHP_EOL;
		return $TimeLimitCheck;
	}else{ //File was not found, so assume the check passes because they haven't used it before
		$TimeLimitCheck = array();
		$TimeLimitCheck[] = true;
		$TimeLimitCheck[] = 0;
		return $TimeLimitCheck;
	}
}

function SetCooldown($foldername, $filename){
	echo "SET COOLDOWN" . PHP_EOL;
	if ($foldername !== NULL) $folder_symbol = "/";
	include("constants.php");
	$path = $DIR.$foldername.$folder_symbol; //echo "PATH: $path" . PHP_EOL;
	$now = new DateTime();
	VarSave($foldername, $filename, $now);
}

function FormatTime($seconds){
	//compare time
	$dtF = new \DateTime('@0');
	$dtT = new \DateTime("@$seconds");
	//ymdhis
	$formatted = $dtF->diff($dtT)->format(' %y years, %m months, %d days, %h hours, %i minutes and %s seconds');
	//echo "formatted: " . $formatted . PHP_EOL;
	//remove 0 values
	$formatted = str_replace(" 0 years,", "" ,$formatted);
	$formatted = str_replace(" 0 months,", "" ,$formatted);
	$formatted = str_replace(" 0 days,", "" ,$formatted);
	$formatted = str_replace(" 0 hours,", "" ,$formatted);
	$formatted = str_replace(" 0 minutes and", "" ,$formatted);
	$formatted = str_replace(" 0 seconds,", "" ,$formatted);
	$formatted = trim($formatted);
	//echo "new formatted: " . $formatted . PHP_EOL;
	return $formatted;
}

function TimeArrayToSeconds($array){
	$y = $array['year'];
	$m = $array['month'];
	$d = $array['day'];
	$h = $array['hour'];
	$i = $array['min'];
	$s = $array['sec'];
	$seconds =
	($s) +
	($i * 60) +
	($h * 3600) +
	($d * 86400) +
	($m * 2629746) +
	($y * 31556952);
	return $seconds;
}
?>
