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

		// Get globals
		$query = "CALL get_global('API_URL')";
		$this->api_url = $_DB['botstore']->doQueryAns($query);

		// Set local variables
		$this->api_url = $this->api_url;
	}
}