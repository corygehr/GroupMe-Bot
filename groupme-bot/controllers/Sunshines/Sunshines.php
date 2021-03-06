<?php
	/**
	 * Sunshines.php
	 * Contains the Class for the Sunshines Controller
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot;

class Sunshines extends \Thinker\Framework\Controller
{
	/**
	 * defaultSubsection()
	 * Returns the default subsection for this Controller
	 *
	 * @access public
	 * @static
	 * @return string Subsection Name
	 */
	public static function defaultSubsection()
	{
		return 'add';
	}

	/**
	 * __construct()
	 * Override the default constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		// Run parent routines
		parent::__construct();

		// Verify session variables are set
		// If not, send them to GroupMeAuth
		if(!array_key_exists("GROUPME_TOKEN", $_SESSION) || !array_key_exists("USER_ID", $_SESSION))
		{
			\Thinker\Http\Redirect::go("GroupMeAuth", "redirect");
		}
	}

	/**
	 * add()
	 * Passes data back for the 'add' subsection
	 *
	 * @access public
	 */
	public function add()
	{
		global $_DB;

		// Provide user groups
		$groups = $this->get_user_groups($_SESSION['USER_ID']);

		if($groups)
		{
			$this->set('groups', $groups);
		}
		else
		{
			// User has no groups? 404
			\Thinker\Http\Redirect::error(404);
		}

		// What kind of request is this?
		switch($_SERVER['REQUEST_METHOD'])
		{
			case 'POST':
				// Get form items and save
				$group = \Thinker\Http\Request::post('group', true);
				$message = \Thinker\Http\Request::post('message', true);
				$when = \Thinker\Http\Request::post('when', true);

				// Check if values exist
				if($group && $message && $when)
				{
					// Verify user has access to the group
					$query = "SELECT COUNT(1)
							  FROM users_groups 
							  WHERE user_id = :userId 
							  AND group_id = :groupId
							  LIMIT 1";
					$params = array(':userId' => $_SESSION['USER_ID'], ':groupId' => $group);

					if($_DB['botstore']->doQueryAns($query, $params))
					{
						// Submit
						switch($when)
						{
							case "now":
								if($this->show_now($group, $message))
								{
									$this->set('message', "Sent successfully!");
								}
							break;

							case "later":
								if($this->add_to_database($group, $message))
								{
									$this->set('message', "Submitted successfully!");
								}
							break;

							default:
								$this->set('message', "Invalid time option.");
							break;
						}
					}
					else
					{
						$this->set('message', "Stop trying to post to groups you don't belong in.");
					}
				}
				else
				{
					$this->set('message', 'One or more required fields were missing.');
				}
			break;
		}

		return true;
	}

	/**
	 * add_to_database()
	 * Adds the sunshine to the database
	 *
	 * @author Cory Gehr
	 * @param group Target Group ID
	 * @param 
	 * @param sent If true, sunshine has already been sent to the group
	 * @return True on Success, False on Failure
	 */
	private function add_to_database($group, $message, $sent = false)
	{
		global $_DB;

		$displayedTime = null;

		if($sent)
		{
			$displayedTime = date('Y-m-d H:i:s');
		}

		// Sanitize input / validate

		// Submit
		$query = "INSERT INTO sunshine_queue(group_id, user_id, message, created, displayed)
				  VALUES(:groupId, :userId, :message, NOW(), :displayed)";
		$params = array(':groupId' => $group, ':userId' => $_SESSION['USER_ID'], ':message' => $message, ':displayed' => $displayedTime);

		// Execute query
		return $_DB['botstore']->doQuery($query, $params);
	}

	/**
	 * get_user_groups()
	 * Gets all groups the user is in
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param user_id User's GroupMe ID
	 * @return Array of Groups ([id] => name)
	 */
	private function get_user_groups($user_id)
	{
		global $_DB;

		// Did we get the right input?
		if($user_id)
		{
			// Get groups
			$query = "SELECT g.group_id, g.name
					  FROM groups g 
				  	  JOIN users_groups ug ON ug.group_id = g.group_id
					  WHERE ug.user_id = :userId
					  ORDER BY g.name";
			$params = array(':userId' => $user_id);

			$list = $_DB['botstore']->doQueryArr($query, $params);

			// If we got a list, format it
			if($list)
			{
				$results = array();

				// Process results
				foreach($list as $l)
				{
					$results[$l['group_id']] = $l['name'];
				}

				return $results;
			}
		}

		return false;
	}

	/**
	 * show_now()
	 * Sends the sunshine to the group immediately
	 *
	 * @author Cory Gehr
	 * @return True on Success, False on Failure
	 */
	private function show_now($group, $message)
	{
		global $_DB;

		// Sanitize input / validate

		// Create a callback response object to send the message
		$sender = new \GroupMeBot\BotResource\CallbackResponse($group);

		$sender->text = 'New Sunshine! "' . $message . '"';
		$sender->send();

		if($sender->message_sent())
		{
			// Add contents to DB for tracking
			return $this->add_to_database($group, $message, true);
		}
		else
		{
			return false;
		}
	}
}
?>