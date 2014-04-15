SlimTimer PHP Interface
=======================

About
-----
I use SlimTimer to monitor how long I spend on tasks and love how simplistic
and useful it is. I was wanting to automate a weekly export so that I did
not have to remember to do it and was surprised that I could not find a
pure PHP implementation. I found one using the Zend Framework but wanted
one to run independently of that. In general the code will return a
JSON object for you to play with so remember that you may need to
cast the various things you need to pass.

Requirements
------------
PHP 5+ incl curl & simplexml

Disclaimer
----------
I am not affiliated in any way with SlimTimer and all rights to it are property
of the original developer. You use this bit of code at your own risk,
please provide any feedback you like or better yet fork the repository and
contribute.

For more information on Slimtimer please visit the website : http://slimtimer.com/

Testing
-------
To run the tests you must use PHPUnit and add a config.ini file. Below is
an example of the config file.

	email = "test@foo.com"
	password = "1234"
	coworkers[] = "me@foo.com"
	coworkers[] = "you@foo.com"
	reporters[] = "us@foo.com"
	reporters[] = "them@foo.com"

Example usage
-------------

	$email = '';
	$password = '';

	$s = new SlimTimer();
	$authResponse = $s->authenticate($email, $password);

	$tasks = $s->listTasks();
	var_dump($tasks);

	$task = $s->createTask("Test via API");
	$s->updateTask($task->id, null, array('foo', 'bar'));
	$s->showTask($task->id);
	$s->deleteTask($task->id);
	$s->listTimes();
	$s->listTimesForTask($task->id);
	$time = $s->createTime($task->id, date('Y-m-d H:i:s'), 1);
	$s->showTime($time->id);
	$s->updateTime($task->id, $time->id, 10, $time->{'start-time'});
	$s->deleteTime($time->id);
