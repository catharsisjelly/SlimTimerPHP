<?php

require_once 'SlimTimer.php';

class SlimTimerTest extends PHPUnit_Framework_TestCase
{
	var $class = null;
	var $config = array();
	var $tasks = array();
	
	protected static $testTaskID;

	protected function setUp()
	{
		$this->class = new SlimTimer();
		
		$configFile = 'config.ini';
		if(!file_exists($configFile))
			throw new Exception('The test suite requires you to have an ini file in the same dir as the class');
		
		$this->config = parse_ini_file($configFile);
		
		if(!array_key_exists('email', $this->config))
			throw new Exception('Your config file should have an email entry');
		
		if(!array_key_exists('password', $this->config))
			throw new Exception('Your config file should have a password entry');
	}
	
	protected function tearDown()
	{
		foreach($this->tasks as $task)
		{
			$this->class->deleteTask((int) $task->id);
		}
	}

	protected function authenticate()
	{
		return $this->class->authenticate($this->config['email'], $this->config['password'], true);
	}

	public function testAuthenticateFail()
	{
		$return = $this->class->authenticate('bad@bad.com', 'bad');
		$this->assertFalse($return);
	}
	
	public function testAuthenticatePass()
	{
		$return = $this->authenticate();
		$this->assertTrue($return);
	}
	
	public function testCreateTaskBasic()
	{
		$taskName = 'testCreateTaskBasic';
		$this->authenticate();
		$task = $this->class->createTask($taskName);
		$this->tasks[] = $task;
		$this->assertTrue(is_object($task));
		$this->assertEquals($taskName, $task->name);
		$this->assertTrue(((int) $task->id > 0));
	}
	
	public function testCreateTaskWithTags()
	{
		$taskName = 'testCreateTaskWithTags';
		$tags = array('tag1', 'tag2');
		$this->authenticate();
		$task = $this->class->createTask($taskName, $tags);
		$this->tasks[] = $task;
		$this->assertTrue(is_object($task));
		$this->assertEquals($taskName, $task->name);
		$this->assertTrue(((int) $task->id > 0));
		$this->assertEquals($tags, explode(',', $task->tags));
	}
	
	public function testCreateTaskWithCoworkers()
	{
		$taskName = 'testCreateTaskWithCoworkers';
		if(array_key_exists('coworkers', $this->config))
		{
			$this->authenticate();
			$task = $this->class->createTask($taskName, array(), $this->config['coworkers']);
			$this->tasks[] = $task;
			
			$this->assertTrue(is_object($task));
			$this->assertEquals($taskName, $task->name);
			$this->assertTrue(((int) $task->id > 0));
			$this->assertTrue(is_object($task->coworkers));
			foreach($task->coworkers->person as $person)
			{
				$this->assertTrue(in_array($person->email, $this->config['coworkers']));
			}
			
		} else {
			$this->markTestSkipped("Test skipped due to missing coworkers in the config");
		}
	}
	
	public function testCreateTaskWithReporters()
	{
		$taskName = 'testCreateTaskWithReporters';
		if(array_key_exists('reporters', $this->config))
		{
			$this->authenticate();
			$task = $this->class->createTask($taskName, array(), array(), $this->config['reporters']);
			$this->tasks[] = $task;
			$this->assertTrue(is_object($task));
			$this->assertEquals($taskName, $task->name);
			$this->assertTrue(((int) $task->id > 0));
			$this->assertTrue(is_object($task->reporters));
			
			foreach($task->reporters->person as $person)
			{
				$this->assertTrue(in_array($person->email, $this->config['reporters']));
			}
			
		} else {
			$this->markTestSkipped("Test skipped due to missing reporters in the config");
		}
	}
	
	public function testCreateTaskEmptyName()
	{
		$this->setExpectedException('Exception');
		$this->authenticate();
		$task = $this->class->createTask('');
	}
	
	public function testUpdateTaskName()
	{
		$taskName = 'UpdateTask';
		$this->authenticate();
		$task = $this->class->createTask($taskName);
		$this->tasks[] = $task;
		
		// Update the task
		$task = $this->class->updateTask($task->id, 'Jim');
		$this->assertTrue(is_object($task));
		$this->assertEquals('Jim', $task->name);
		$this->assertTrue(((int) $task->id > 0));
	}
	
	public function testUpdateTaskNoName()
	{
		$taskName = 'UpdateTaskNoName';
		$this->authenticate();
		$task = $this->class->createTask($taskName);
		$this->tasks[] = $task;
		
		// Update the task
		$task = $this->class->updateTask($task->id);
		$this->assertTrue(is_object($task));
		$this->assertEquals($taskName, $task->name);
		$this->assertTrue(((int) $task->id > 0));
	}
	
	public function testUpdateTaskBadID()
	{
		$this->markTestIncomplete('Not implemented yet');
	}
	
	public function testCompleteTask()
	{
		$taskName = 'CompleteTask';
		$this->authenticate();
		$task = $this->class->createTask($taskName);
		$this->tasks[] = $task;
		
		$time = time();
		$completedTask = $this->class->completeTask($task->id, date('Y-m-d H:i:s', $time));
		$this->assertRegExp('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/', (string) $completedTask->{'completed-on'});
		$this->assertNotNull(strtotime((string) $completedTask->{'completed-on'}));
		$this->assertTrue(strtotime((string) $completedTask->{'completed-on'}) >= $time);
	}
	
	public function testShowTask()
	{
		$taskName = 'ShowTask';
		$this->authenticate();
		$task = $this->class->createTask($taskName);
		$this->tasks[] = $task;
		$taskFromAPI = $this->class->showTask($task->id);
		$this->assertEquals($task, $taskFromAPI);
	}
	
	public function testDeleteTask()
	{
		$taskName = 'Bob';
		$this->authenticate();
		$task = $this->class->createTask($taskName);
		$return = $this->class->deleteTask($task->id);
		$this->assertTrue($return);
	}
	
	public function testListTasks()
	{
		$this->authenticate();
		$taskIDs = array();
		for($i = 1; $i <= 3; $i++)
		{
			$task = $this->class->createTask('listTask'.$i);
			$this->tasks[] = $task;
			$taskIDs[] = $task->id;
		}
		$tasks = $this->class->listTasks();
		
		foreach($tasks->task as $task)
			$this->assertTrue(in_array((int) $task->id, $taskIDs));
	}
	
	public function testCreateTime()
	{
		$this->authenticate();
		$duration = 3600;
				
		$task = $this->class->createTask('createTimeTask');
		$this->tasks[] = $task;
		$time = $this->class->createTime($task->id, $duration);
		$created = strtotime($time->{'created-at'});
		$endTime = strtotime($time->{'end-time'});
		$this->assertTrue($time->id > 0);
		$this->assertEquals($task->id, $time->task->id);
		$this->assertEquals((float) $time->task->hours, (float) 1.0);
	}
	
	public function testUpdateTime()
	{
		$this->authenticate();
		$duration = 3600;
				
		$task = $this->class->createTask('createTimeTask');
		$this->tasks[] = $task;
		$time = $this->class->createTime($task->id, $duration);
		$time = $this->class->updateTime($time->id, $task->id, $duration);
		$created = strtotime($time->{'created-at'});
		$endTime = strtotime($time->{'end-time'});
		$this->assertTrue($time->id > 0);
		$this->assertEquals($task->id, $time->task->id);
		$this->assertEquals((float) $time->task->hours, (float) 1.0);
	}
	
}

?>
