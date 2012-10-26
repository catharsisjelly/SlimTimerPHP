<?php

require_once 'SlimTimer.php';

/**
 * This tester looks for a config.ini file and in there you can put your details
 */

$email = '';
$password = '';

if(file_exists('config.ini'))
{
	$config = parse_ini_file('config.ini');
	$email = $config['email'];
	$password = $config['password'];
}

$s = new SlimTimer($apiKey);
$userId = $s->authenticate($email, $password, true);
//$tasks = $s->listTasks($userId);
//$s->createTask($userId, "Test via API");
//$s->updateTask($userId, 1879734, null, array('foo', 'bar'));
//$s->showTask($userId, 1899512);
//$s->deleteTask($userId, 1899512);
//$s->listTimes($userId);
//$s->listTimesForTask($userId, 1879734);
//$s->createTime($userId, 1879734, date('Y-m-d H:i:s'), 1);
//$time = $s->showTime($userId, 19016444);
//$s->updateTime($userId, 1879734, $time->id, 10, $time->{'start-time'});
//$s->deleteTime($userId, $time->id);

?>