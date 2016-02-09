<?php
	/**
	 * models/BotResource/Mention.php 
	 * Contains the Mention class
	 * @see https://gist.github.com/jakebathman/0830789c9cd4167cd1da
	 * @todo
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class Mention extends Attachment
{
	private $group_id;
	private $message;
	private $users;

	/**
	 * __construct()
	 * Constructor for the Mention class
	 *
	 * @access public
	 * @param group_id Group the message is being sent to
	 */
	public function __construct($group_id)
	{
		$this->group_id = $group_id;
		$this->users = array();
	}

	/**
	 * add_message_string()
	 * Adds the message being sent
	 *
	 * @access public
	 * @param message Message being sent
	 */
	public function add_message_string($message)
	{
		$this->message = $message;
	}

	/**
	 * add_user()
	 * Adds a user to be mentioned
	 * 
	 * @access public
	 * @param id User ID
	 * @param name User Name
	 * @return False if user isn't in group
	 */
	public function add_user($id)
	{
		global $_DB;

		// Ensure the user exists in the group
		$query = "SELECT nickname
				  FROM users_groups 
				  WHERE user_id = :user 
				  AND group_id = :group 
				  LIMIT 1";
		$params = array(':user' => $id, ':group' => $this->group_id);

		$nickname = $_DB['botstore']->doQueryAns($query, $params);

		if($nickname)
		{
			$this->users[] = array("nickname" => $nickname, "user_id" => $id);
		}
		else
		{
			return false;
		}
	}

	/**
	 * compile_attributes()
	 * Returns all attributes in an array for processing
	 * Note that this class requires the message being sent so
	 * it can appropriately tag people
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return Array of attributes
	 */
	public function compile_attributes()
	{
		// Create return variable
		$values = array();

		// Proceed if we have all the data we need
		if($this->message && !empty($this->users))
		{
			$locis = array();
			$user_ids = array();
			// Store the furthest match of a user in the text
			// This helps if the user is mentioned more than once in a message
			$furthest_match = array();

			foreach($this->users as $u)
			{
				// Find user nickname in string
				$needle = "@".$u['nickname'];

				// Offset to search in string for matching user
				$offset = 0;

				if(array_key_exists($u['user_id'], $furthest_match))
				{
					$offset = $furthest_match[$u['user_id']]+1;
				}

				// Find instance
				$first_ind = strpos($this->message, $needle, $offset);

				if($first_ind)
				{
					// Length needs to include the '@'
					$length = strlen($needle);

					// Add references to array
					$locis[] = array($first_ind, $length);
					$user_ids[] = $u['user_id'];

					// Update the furthest match for this user in the message
					$furthest_match[$u['user_id']] = $first_ind;
				}
				// We ignore the reference if they can't be found
			}

			// Create the values array
			$values = array(
				"loci" => $locis,
				"type" => $this->get_type(),
				"user_ids" => $user_ids
			);
		}

		return $values;
	}

	/**
	 * get_type()
	 * Returns the type of the attachment
	 *
	 * @access public
	 * @return Type Name
	 */
	public function get_type()
	{
		return "mentions";
	}
}