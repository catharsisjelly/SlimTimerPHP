<?php

$apiKey = 'ae4b927814a4844633f7df27f555b7';
$email = '';
$password = '';

$s = new SlimTimer($apiKey);
$userId = $s->authenticate($email, $password);

//$tasks = $s->listTasks($userId);
//var_dump($tasks);

//$s->createTask($userId, "Test via API");
//$s->updateTask($userId, 1879734, null, array('foo', 'bar'));
//$s->showTask($userId, 1899512);
//$s->deleteTask($userId, 1899512);
//$s->listTimes($userId);
//$s->listTimesForTask($userId, 1879734);
//$s->createTime($userId, 1879734, date('Y-m-d H:i:s'), 1);
$time = $s->showTime($userId, 19016444);
//var_dump($time->{'start-time'});
//var_dump($s->updateTime($userId, 1879734, $time->id, 10, $time->{'start-time'}));
$s->deleteTime($userId, $time->id);

class SlimTimer
{
	private $ch;

	private $_mainURL = 'http://slimtimer.com/';
	private $_accessToken = null;
	private $_apiKey = null;

	public function __construct($apiKey)
	{
		$this->_apiKey = $apiKey;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/xml'
		));
		$this->ch = $ch;
	}

	/**
	 * Authenticate the user and grab a token id for them, returns the userId
	 *
	 * @param string $email 
	 * @param string $password 
	 * @return int
	 */
	public function authenticate($email, $password)
	{
		$params = array(
			'user' => array(
				'email' => $email,
				'password' => $password
			),
			'api_key' => $this->_apiKey
		);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/token');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $content=curl_exec($this->ch);

		$xml = simplexml_load_string($content);
		if(!$xml)
			return false;

		$this->_accessToken = (string) $xml->{'access-token'};
		
		return (int) $xml->{'user-id'};
	}

	/**
	 * List the tasks in your list 
	 *
	 * @param int $user_id
	 * @param bool $showCompleted
	 * @param array $role
	 * @param int $offset
	 * @return obj|false
	 */
	public function listTasks($user_id, $showCompleted = true, array $role = array('owner','coworker'), $offset = null)
	{
		$string = array();
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'show_completed' => ($showCompleted ? 'yes' : 'no'),
			'role' => implode(',', $role),
			'offset' => (is_int($offset) ? $offset : 0)
		);
		
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/tasks?'.http_build_query($params));
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->ch);
		
		return $this->_tidyXML($content);
	}

	/**
	 * Create a task
	 *
	 * @param int $user_id 
	 * @param string $name 
	 * @param array $tags 
	 * @param array $coworkers 
	 * @param array $reporters 
	 * @return obj|false
	 */
	public function createTask($user_id, $name, array $tags = array(), array $coworkers = array(), array $reporters = array())
	{
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'task' => array(
				'name' => $name,
				'tags' => implode(',', $tags),
				'coworker_emails' => implode(',', $coworkers),
				'reporter_emails' => implode(',', $reporters)
			)
		);
				
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/tasks');
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));

		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
 	// @param string $completed Date format yyyy-mm-dd hh:mm:ss
	/**
	 * Update a task
	 *
	 * @param int $user_id 
	 * @param int $task_id 
	 * @param string $name 
	 * @param array $tags 
	 * @param array $coworkers 
	 * @param array $reporters 
	 * @param string $completed 
	 * @return obj|false
	 */
	public function updateTask($user_id, $task_id, $name = null, array $tags = array(), array $coworkers = array(), array $reporters = array(), $completed = null)
	{
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'task' => array(
				'name' => $name,
				'tags' => implode(',', $tags),
				'coworker_emails' => implode(',', $coworkers),
				'reporter_emails' => implode(',', $reporters),
				'completed_on' => $this->_checkDate($completed)
			)
		);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/tasks/'.$task_id);

		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Helper function to easily and quickly complete a task, by default complete it now unless you specify a date
	 *
	 * @param int $user_id 
	 * @param int $task_id 
	 * @param string $date 
	 * @return obj|false
	 */
	public function completeTask($user_id, $task_id, $date = null)
	{
		if(null === $date)
			$date = date('Y-m-d H:i:s');
			
		return $this->updateTask($user_id, $task_id, null, array(), array(), array(), $date);
	}
	
	/**
	 * Show a task
	 *
	 * @param int $user_id 
	 * @param int $task_id 
	 * @return obj|false
	 */
	public function showTask($user_id, $task_id)
	{
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
		);
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/tasks/'.$task_id.'?'.http_build_query($params));
		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Delete a task
	 *
	 * @param int $user_id 
	 * @param int $task_id 
	 * @return bool
	 */
	public function deleteTask($user_id, $task_id)
	{
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
		);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/tasks/'.$task_id.'?'.http_build_query($params));
		$content = curl_exec($this->ch);
		if(!$content)
			return true;
		return false;
	}
	
	/**
	 * List the times for any particular task
	 *
	 * @param int $user_id 
	 * @param int $task_id 
	 * @param string $startDate 
	 * @param string $endDate 
	 * @param string $offset 
	 * @return obj|false
	 */
	public function listTimesForTask($user_id, $task_id, $startDate = null, $endDate = null, $offset = null)
	{
		// /users/user_id/time_entries
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'timeentry' => array(
				'range_start' => $this->_checkDate($startDate), 
				'range_end' => $this->_checkDate($endDate),
				'offset' => (is_int($offset) ? $offset : 0)
			)
		);

		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/tasks/'.$task_id.'/time_entries?'.http_build_query($params));
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * List the timesentries in the account
	 *
	 * @param int $user_id 
	 * @param string $startDate 
	 * @param string $endDate 
	 * @param int $offset 
	 * @return obj|false
	 */
	public function listTimes($user_id, $startDate = null, $endDate = null, $offset = null)
	{
		// /users/user_id/time_entries
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'timeentry' => array(
				'range_start' => $this->_checkDate($startDate), 
				'range_end' => $this->_checkDate($endDate),
				'offset' => (is_int($offset) ? $offset : 0)
			)
		);
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/time_entries?'.http_build_query($params));
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Create a time entry
	 *
	 * @param int $user_id 
	 * @param int $task_id 
	 * @param int $duration
	 * @param string $startTime DEFAULT=now
	 * @param string $endTime 
	 * @param array $tags 
	 * @param string $comments 
	 * @param string $progress 
	 * @return obj|false
	 */
	public function createTime($user_id, $task_id, $duration, $startTime = null, $endTime = null, array $tags = array(), $comments = "", $progress = false)
	{
		if($duration <= 0)
			throw new Exception('Duration must be more than 0');
			
		if($startTime === null)
			$startTime = date('Y-m-d H:i:s');

		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'time_entry' => array(
				// required
				'start_time' => $this->_checkDate($startTime),
				'task_id' => (int) $task_id,
				'duration_in_seconds' => (int) $duration,
				// extra
				'end_time' => $this->_checkDate($endTime),
				'tags' => implode(',', $tags),
				'comments' => $comments,
				'in_progress' => $progress,
			)
		);
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/time_entries');
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));
		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Show a time entry
	 *
	 * @param int $user_id 
	 * @param int $time_id 
	 * @return obj|false
	 */
	public function showTime($user_id, $time_id)
	{
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken
		);
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/time_entries/'.$time_id.'?'.http_build_query($params));
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Update a time entry
	 *
	 * @param int $user_id 
	 * @param int $time_id 
	 * @param int $task_id 
	 * @param int $duration 
	 * @param string $startTime 
	 * @param string $endTime 
	 * @param array $tags
	 * @param string $comments 
	 * @param string $progress 
	 * @return obj|false
	 * @author chris
	 */
	public function updateTime($user_id, $task_id, $time_id, $duration, $startTime, $endTime = null, array $tags = array(), $comments = "", $progress = false)
	{
		if($duration <= 0)
			throw new Exception('Duration must be more than 0');

		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken,
			'time_entry' => array(
				// required
				'start_time' => $this->_checkDate($startTime),
				'task_id' => (int) $task_id,
				'duration_in_seconds' => (int) $duration,
				// extra
				'end_time' => $this->_checkDate($endTime),
				'tags' => implode(',', $tags),
				'comments' => $comments,
				'in_progress' => $progress,
			)
		);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/time_entries/'.$time_id);

		$content = curl_exec($this->ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Delete a time entry
	 *
	 * @param int $user_id 
	 * @param int $time_id 
	 * @return bool
	 */
	public function deleteTime($user_id, $time_id)
	{
		$params = array(
			'api_key' => $this->_apiKey,
			'access_token' => $this->_accessToken
		);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($this->ch, CURLOPT_URL, $this->_mainURL.'/users/'.$user_id.'/time_entries/'.$time_id.'?'.http_build_query($params));
		$content = curl_exec($this->ch);
		if(!$content)
			return true;
		return false;
	}
	
	/**
	 * Checks the formatting of the date, should be YYYY-MM-DD HH-MM-SS
	 *
	 * @param string $string 
	 * @return string|false
	 */
	private function _checkDate($string)
	{
		if(!$string)
			return false;
		
		if($string && preg_match("/\d{4}-\d{2}-\d{2}T?\d{2}:\d{2}:\d{2}/", $string))
			return $string;
		
		throw new Exception("Date format must be yyyy-mm-dd hh:mm:ss");
	}
	
	/**
	 * Take the XML from the server, tidy it and return either an object or false
	 *
	 * @param string $content 
	 * @return obj|false
	 */
	private function _tidyXML($content)
	{
		$content = preg_replace("/[\r\n\s]{2,}/", "", $content);
		$xml = simplexml_load_string($content);
		if(!$xml)
			return false;

		return json_decode(json_encode($xml));
	}

}