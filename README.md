SlimTimer PHP Interface
=======================

About
-----
I use SlimTimer to monitor how long I spend on tasks and love how simplistic and useful it is. I was wanting to automate a weekly export so that I did not have to remember to do it and was surprised that I could not find a pure PHP implementation. I found one using the Zend Framework but wanted one to run independently of that.

Requirements
------------
PHP 5+ incl curl

Disclaimer
----------
I am not affiliated in any way with SlimTimer and all rights to it are property of the original developer. You use this bit of code at your own risk, please provide any feedback you like or better yet branch the repo and contribute.

Todo
----
Test Suite
More docs
Remove the need to have userId with all requests

Example usage
-------------
$apiKey = 'ae4b927814a4844633f7df27f555b7';
$email = '';
$password = '';

$s = new SlimTimer($apiKey);
$userId = $s->authenticate($email, $password);

$tasks = $s->listTasks($userId);
var_dump($tasks);

$s->createTask($userId, "Test via API");
$s->updateTask($userId, 1879734, null, array('foo', 'bar'));
$s->showTask($userId, 1899512);
$s->deleteTask($userId, 1899512);
$s->listTimes($userId);
$s->listTimesForTask($userId, 1879734);
$s->createTime($userId, 1879734, date('Y-m-d H:i:s'), 1);
$time = $s->showTime($userId, 19016444);
$s->updateTime($userId, 1879734, $time->id, 10, $time->{'start-time'});
$s->deleteTime($userId, $time->id);
