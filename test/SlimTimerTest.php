<?php

require_once 'SlimTimer.php';

class SlimTimerTest extends PHPUnit_Framework_TestCase
{
	var $class = null;
	var $config = array();
	var $tasks = array();
	
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


	public function testAuthenticateFail()
	{
		$return = $this->class->authenticate('bad@bad.com', 'bad');
		$this->assertTrue(is_array($return));
		$this->assertArrayHasKey('user-id', $return);
		$this->assertArrayHasKey('token', $return);
		$this->assertEmpty($return['token']);
		$this->assertEquals(0, $return['user-id']);
	}
	
	public function testAuthenticatePass()
	{
		$return = $this->class->authenticate($this->config['email'], $this->config['password']);
		$this->assertTrue(is_array($return));
		$this->assertArrayHasKey('user-id', $return);
		$this->assertArrayHasKey('token', $return);
		$this->assertNotEmpty($return['token']);
		$this->assertTrue(($return['user-id'] != 0));
	}
	
	public function testCreateTaskBasic()
	{
		$taskName = 'testCreateTaskBasic';
		$this->class->authenticate($this->config['email'], $this->config['password'], true);
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
		$this->class->authenticate($this->config['email'], $this->config['password'], true);
		$task = $this->class->createTask($taskName, $tags);
		$this->tasks[] = $task;
		$this->assertTrue(is_object($task));
		$this->assertEquals($taskName, $task->name);
		$this->assertTrue(((int) $task->id > 0));
		$this->assertEquals($tags, explode(',', $task->tags));
	}
	
	//createTask($name, array $tags = array(), array $coworkers = array(), array $reporters = array())
	
	public function testCreateTaskWithCoworkers()
	{
		$taskName = 'testCreateTaskWithCoworkers';
		if(array_key_exists('coworkers', $this->config))
		{
			$this->class->authenticate($this->config['email'], $this->config['password'], true);
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
			$this->class->authenticate($this->config['email'], $this->config['password'], true);
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
}

?>
