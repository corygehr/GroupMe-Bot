<?php
	/**
	 * models/BotResource/APIRequest.php 
	 * Contains the APIRequest class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class APIRequest extends \Thinker\Framework\Model
{
	// See https://dev.groupme.com/docs/v3
	private $api_url;
	private $method;
	private $params;
	private $path;
	private $sent;

	/**
	 * __construct()
	 * Constructor for the APIRequest class
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string Request Path 
	 * @param string HTTP Method
	 * @param string[] Query Parameters
	 */
	public function __construct($path = "", $method = "GET", $params = array())
	{
		global $_DB;

		// Get globals
		$query = "CALL get_global('API_URL')";
		$this->api_url = $_DB['botstore']->doQueryAns($query);

		// Add starting / if not present in path
		$requestPath = $path;
		if($requestPath && !substr($path, 0, 1) == "/")
		{
			$requestPath = "/" . $requestPath;
		}

		// Set local variables
		$this->api_url = $this->api_url . $requestPath;
		$this->method = $method;
		$this->params = $params;
		$this->path = $path;

		// Reset received notice
		$this->sent = false;
	}

	/**
	 * add_param()
	 * Adds or updates a request parameter
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string Key
	 * @param string Value
	 */
	public function add_param($key, $value)
	{
		$this->params[$key] = $value;
	}

	/**
	 * execute()
	 * Sends the message to the GroupMe API and returns the result as an associative array
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return Array on Success, False on Failure
	 */
	public function execute()
	{
		// Send only if message hasn't been sent yet
		if(!$this->sent)
		{
			// Send message to GroupMe API
			$options = array(
			    'http' => array(
			        'header'  => "Content-type: application/json\r\n",
			        'method'  => $this->method
			    ),
			);
			
			$context = stream_context_create($options);
			$result = file_get_contents($this->api_url, false, $context);

			$this->sent = true;

			return json_decode($result, true);
		}
		else
		{
			return false;
		}
	}

	/**
	 * request_sent()
	 * Tells if this message has been sent to the GroupMe API
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return True if Yes, False if No
	 */
	public function request_sent()
	{
		return $this->sent;
	}
}