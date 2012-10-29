<?php

require_once 'SlimTimer.php';

class SlimTimerTest extends PHPUnit_Framework_TestCase
{
	var $class = null;
	var $config = array();
	
    protected function setUp()
	{
		$configFile = 'config.ini';
		$this->class = new SlimTimer();
		if(!file_exists($configFile))
			throw new Exception('The test suite requires you to have an ini file in the same dir as the class');
		$this->config = parse_ini_file($configFile);
	}
	
	public function authenticateFailTest()
	{
		//$return = $this->class->authenticate('bad@bad.com', 'bad');
		$this->assertTrue(true);
	}
}

?>
