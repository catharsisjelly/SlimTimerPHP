<?php

/**
 * A class to interact with the SlimTimer API
 * 
 * @author Chris Lock <code@catharsis.co.uk>
 * @see http://slimtimer.com/help/api
 * @see https://github.com/catharsisjelly/SlimTimerPHP
 */

class SlimTimer
{	
	// The URL to use
	const MAIN_URL = 'http://slimtimer.com/';
	
	// This API key is mine, feel free to use your own if you want
	const API_KEY = 'ae4b927814a4844633f7df27f555b7';
	
	/**
	 * Class var to hold the curl handle
	 * @var resource
	 */
	private $_ch;

	/**
	 * The user ID that is used for each request
	 * @var int
	 */
	private $_userID = null;
	
	/**
	 * The Access token that is grabbed from the authentication method
	 * @var string 
	 */
	private $_accessToken = null;

	public function __construct()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/xml'
		));
		$this->_ch = $ch;
	}
	
	/**
	 * Set the user ID and Access token
	 * 
	 * @param int $userId
	 * @param string $token
	 */
	public function setUserAndToken($userID, $token)
	{
		$this->_userID = $userID;
		$this->_accessToken = $token;
	}

	/**
	 * Authenticate the user and grab a token id for them, returns a keyed array of the user-id & token
	 *
	 * @param string $email 
	 * @param string $password 
	 * @param bool $setToken 
	 * @return array
	 */
	public function authenticate($email, $password)
	{
		$params = array(
			'user' => array(
				'email' => $email,
				'password' => $password
			),
			'api_key' => self::API_KEY
		);
		curl_setopt($this->_ch, CURLOPT_POST, 1);
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/token');
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $content=curl_exec($this->_ch);

		$xml = simplexml_load_string($content);
		
		if(!$xml)
		{
			error_log("SlimTimerPHP: Invalid API Response $content");
			return false;
		}

		if((int) $xml->{'user-id'} == 0)
		{
			error_log("SlimTimerPHP: User authentic auth failed");
			return false;
		}

		$this->_accessToken = (string) $xml->{'access-token'};
		$this->_userID = (int) $xml->{'user-id'};

		return true;
	}

	/**
	 * List the tasks in your list 
	 *
	 * @param bool $showCompleted
	 * @param array $role an array of values from owner,coworker,reporter
	 * @param int $offset
	 * @return obj|false
	 */
	public function listTasks($showCompleted = true, array $role = array('owner','coworker'), $offset = null)
	{
		$string = array();
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
			'show_completed' => ($showCompleted ? 'yes' : 'no'),
			'role' => implode(',', $role),
			'offset' => (is_int($offset) ? $offset : 0)
		);

		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/tasks?'.http_build_query($params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->_ch);
		
		return $this->_tidyXML($content);
	}

	/**
	 * Create a task
	 *
	 * @param string $name 
	 * @param array $tags 
	 * @param array $coworkers 
	 * @param array $reporters 
	 * @return obj|false
	 */
	public function createTask($name, array $tags = array(), array $coworkers = array(), array $reporters = array())
	{
		if(empty($name))
			throw new Exception('name parameter cannot be empty');
		
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
			'task' => array(
				'name' => $name,
				'tags' => implode(',', $tags),
				'coworker_emails' => implode(',', $coworkers),
				'reporter_emails' => implode(',', $reporters)
			)
		);
				
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/tasks');
		curl_setopt($this->_ch, CURLOPT_POST, 1);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($params));

		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Update a task
	 *
	 * @param int $task_id 
	 * @param string $name 
	 * @param array $tags 
	 * @param array $coworkers 
	 * @param array $reporters 
	 * @param string $completed A date/time in the format YYYY-MM-DD HH-MM-SS
	 * @return obj|false
	 */
	public function updateTask($task_id, $name = null, array $tags = array(), array $coworkers = array(), array $reporters = array(), $completed = null)
	{
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
			'task' => array(
				'name' => $name,
				'tags' => implode(',', $tags),
				'coworker_emails' => implode(',', $coworkers),
				'reporter_emails' => implode(',', $reporters),
				'completed_on' => $this->_checkDate($completed)
			)
		);
		curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/tasks/'.$task_id);

		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Helper function to easily and quickly complete a task, by default complete it now unless you specify a date
	 *
	 * @param int $task_id 
	 * @param string $date 
	 * @return obj|false
	 */
	public function completeTask($task_id, $date = null)
	{
		if(null === $date)
			$date = date('Y-m-d H:i:s');
			
		return $this->updateTask($task_id, null, array(), array(), array(), $date);
	}
	
	/**
	 * Show a task
	 *
	 * @param int $task_id 
	 * @return obj|false
	 */
	public function showTask($task_id)
	{
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
		);
		curl_setopt($this->_ch, CURLOPT_HTTPGET, 1);
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/tasks/'.$task_id.'?'.http_build_query($params));
		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Delete a task
	 *
	 * @param int $task_id 
	 * @return bool
	 */
	public function deleteTask($task_id)
	{
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
		);
		curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/tasks/'.$task_id.'?'.http_build_query($params));
		$content = curl_exec($this->_ch);
		if(!$content)
			return true;
		return false;
	}
	
	/**
	 * List the times for any particular task
	 *
	 * @param int $task_id 
	 * @param string $startDate 
	 * @param string $endDate 
	 * @param string $offset 
	 * @return obj|false
	 */
	public function listTimesForTask($task_id, $startDate = null, $endDate = null, $offset = null)
	{
		// /users/user_id/time_entries
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
			'range_start' => $this->_checkDate($startDate), 
			'range_end' => $this->_checkDate($endDate),
			'offset' => (is_int($offset) ? $offset : 0)
		);

		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/tasks/'.$task_id.'/time_entries?'.http_build_query($params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * List the times entries in the account
	 *
	 * @param string $startDate 
	 * @param string $endDate 
	 * @param int $offset 
	 * @return obj|false
	 */
	public function listTimes($startDate = null, $endDate = null, $offset = null)
	{
		// /users/user_id/time_entries
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken,
			'range_start' => $this->_checkDate($startDate), 
			'range_end' => $this->_checkDate($endDate),
			'offset' => (is_int($offset) ? $offset : 0)
		);
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/time_entries?'.http_build_query($params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Create a time entry
	 *
	 * @param int $task_id 
	 * @param int $duration
	 * @param string $startTime DEFAULT=now
	 * @param string $endTime 
	 * @param array $tags 
	 * @param string $comments 
	 * @param string $progress 
	 * @return obj|false
	 */
	public function createTime($task_id, $duration, $startTime = null, $endTime = null, array $tags = array(), $comments = "", $progress = false)
	{
		if($duration <= 0)
			throw new Exception('Duration must be more than 0');
			
		if($startTime === null)
			$startTime = date('Y-m-d H:i:s');

		$params = array(
			'api_key' => self::API_KEY,
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
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/time_entries');
		curl_setopt($this->_ch, CURLOPT_POST, 1);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($params));
		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Show a time entry
	 *
	 * @param int $time_id 
	 * @return obj|false
	 */
	public function showTime($time_id)
	{
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken
		);
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/time_entries/'.$time_id.'?'.http_build_query($params));
		curl_setopt($this->_ch, CURLOPT_HTTPGET, 1);

		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Update a time entry
	 *
	 * @param int $time_id 
	 * @param int $task_id 
	 * @param int $duration Duration In Seconds
	 * @param string $startTime 
	 * @param string $endTime 
	 * @param array $tags
	 * @param string $comments 
	 * @param string $progress 
	 * @return obj|false
	 * @author chris
	 */
	public function updateTime($time_id, $task_id, $duration, $startTime, $endTime = null, array $tags = array(), $comments = "", $progress = false)
	{
		if($duration <= 0)
			throw new Exception('Duration must be more than 0');

		$params = array(
			'api_key' => self::API_KEY,
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
		
		if($duration)
			$params['time_entry']['duration_in_seconds'] = (int) $duration;
		
		if($task_id)
			$params['time_entry']['task_id'] = $task_id;
			
		curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/time_entries/'.$time_id);

		$content = curl_exec($this->_ch);
		return $this->_tidyXML($content);
	}
	
	/**
	 * Delete a time entry
	 *
	 * @param int $time_id 
	 * @return bool
	 */
	public function deleteTime($time_id)
	{
		$params = array(
			'api_key' => self::API_KEY,
			'access_token' => $this->_accessToken
		);
		curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($this->_ch, CURLOPT_URL, self::MAIN_URL.'/users/'.$this->_userID.'/time_entries/'.$time_id.'?'.http_build_query($params));
		$content = curl_exec($this->_ch);
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
		
		if($string && preg_match("/\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/", $string))
			return $string;
		
		throw new Exception("Date format must be yyyy-mm-dd hh:mm:ss you provided {$string}");
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
		$xml = @simplexml_load_string($content);

		if(!$xml)
			return false;

		return @json_decode(@json_encode($xml));
	}

	public function __unset($name)
	{
		curl_close($this->_ch);
	}

}
