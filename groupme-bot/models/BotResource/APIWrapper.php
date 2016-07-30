<?php
	/**
	 * models/BotResource/APIWrapper.php 
	 * Contains the APIWrapper class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class APIWrapper extends \Thinker\Framework\Model
{
	// See https://dev.groupme.com/docs/v3
	private $api_url;
	private $token;

	/**
	 * __construct()
	 * Constructor for the APIWrapper class
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string Group ID (required to pull token)
	 */
	public function __construct($group_id)
	{
		global $_DB;

		// Get API Url
		$query = "CALL get_global('API_URL')";
		$this->api_url = $_DB['botstore']->doQueryAns($query);

		// Get token
		$query = "CALL get_group_access_token(:groupid)";
		$params = array(':groupid' => $group_id);

		$token = $_DB['botstore']->doQueryAns($query, $params);

		if($token)
		{
			$this->token = $token;
		}

		// Set local variables
		$this->api_url = $this->api_url;
	}

	/**
	 * generate_url()
	 * Generates the URL to hit for the API
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param string Path (include leading /)
	 * @return string Full URL
	 */
	private function generate_url($path)
	{
		// Generate Base URL
		$url = $this->api_url . $path;

		// Include token if we have one
		if($this->token)
		{
			$url .= "?token={$this->token}";
		}

		return $url;
	}

	/**
	 * group_list_members()
	 * Pulls a list of members for a group
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param int Group ID
	 * @return Array of Members
	 */
	public function group_list_members($group_id)
	{
		if($this->is_valid()) 
		{
			// Create the URL
			$url = $this->generate_url("/groups/$group_id");

			// Create options
			$options = array(
			    'http' => array(
			        'header'  => "Content-type: application/json\r\n",
			        'method'  => "GET"
			    ),
			);

			// Call URL
			$context = stream_context_create($options);
			$result = file_get_contents($url, false, $context);

			// Ensure we got a response
			if($result)
			{
				// Parse JSON
				$parsed = json_decode($result, true);

				// Check if we got the expected result
				if(array_key_exists('response', $parsed) && array_key_exists('members', $parsed['response']))
				{
					// Return valid portion of array
					return $parsed['response']['members'];
				}
				else
				{
					// Unexpected response
					return array();
				}
			}
			else
			{
				// Return blank array if there's no response
				return array();
			}
		}
		else
		{
			// Return null array if the wrapper is not valid
			return array();
		}
	}

	/**
	 * group_remove_member()
	 * Removes a member from the group
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param int Group ID
	 * @param int Membership ID (Different than Global User ID!)
	 * @return True on Success, False on Failure
	 */
	public function group_remove_member($group_id, $mem_id)
	{
		// Verify wrapper is valid
		if($this->is_valid())
		{
			// Build URL
			$url = $this->generate_url("/groups/$group_id/members/$mem_id/remove");

			// Create the CURL object
			$removeCurl = curl_init($url);
			// Set type to POST
			curl_setopt($removeCurl, CURLOPT_POST, 1);
			// Execute POST
			$result = curl_exec($removeCurl);
			// Close CURL resource
			curl_close($removeCurl);

			// TODO: Fix to return true if Status Code 200 comes back
			// (Prior attempts only came back null ='[)
			return ($result != null);
		}
		else
		{
			// Invalid
			return false;
		}
	}

	/**
	 * is_valid()
	 * Tells us if the wrapper is ready to process requests
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return True if Yes, False if No
	 */
	public function is_valid()
	{
		return ($this->token != null);
	}
}