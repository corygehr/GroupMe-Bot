<?php
	/**
	 * models/BotResource/CallbackPost.php 
	 * Contains the CallbackPost class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class CallbackPost extends \Thinker\Framework\Model
{
	// See https://dev.groupme.com/tutorials/bots
	public $attachments;
	public $avatar_url;
	public $created_at;
	public $group_id;
	public $id;
	public $name;
	public $sender_id;
	public $sender_type;
	public $source_guid;
	public $system;
	public $text;
	public $user_id;

	// Locally defined items
	public $attachment_ids;
	public $detected_commands;
	public $mentioned_users;
	public $command_notation;

	private $received;

	/**
	 * __construct()
	 * Constructor for the CallbackPost class
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param command_notation String(s) used to denote commands (default: '/')
	 */
	public function __construct($command_notation = '/')
	{
		// Reset received notice
		$this->received = false;
		$this->command_notation = $command_notation;
		$this->mentioned_users = array();
		$this->detected_commands = array();
		$this->attachment_ids = array();
	}

	/**
	 * contains_command()
	 * Returns true if the message contains a command
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return True if command(s) detected, False if not
	 */
	public function contains_command()
	{
		// See if there are multiple commands present (we may only use the first)
		if(is_array($this->command_notation)) {
			foreach($this->command_notation as $n) {
				// We only want to know if one is found
				return (substr($this->text, 0, 1) === $n);
			}
		}
		else {
			return (substr($this->text, 0, 1) === $this->command_notation);
		}

	}

	/**
	 * get_command()
	 * Returns the command name, if provided
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param preserve_notation If true, saves the command notation char (default: false)
	 * @return Command value if provided, null if none
	 */
	public function get_command($preserve_notation = false)
	{
		if($this->contains_command()) {
			$value = explode(" ", strstr(trim($this->text), $this->command_notation))[0];

			if($preserve_notation) {
				return strtolower($value);
			}
			else {
				return strtolower(str_replace($this->command_notation, "", $value));
			}
		}
		else {
			return null;
		}
	}

	/**
	 * get_command_parameters()
	 * Gets the parameters sent with the command
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return Array of parameter values, in the order they were received
	 */
	public function get_command_parameters()
	{
		if($this->contains_command())
		{
			$params = array();

			// Parse message text, params are separated by space
			// If the param has multiple words, it should be surrounded in ""
			$exp = explode(" ", $this->text);

			// Start at 1 to skip the actual command
			for($i=1;$i<count($exp);$i++)
			{
				// Get the value at this position
				$e = $exp[$i];

				// See if the parameter is more than one word
				if(substr($e, 0, 1) == '"')
				{
					// Make sure the quotes aren't around a single word
					if(substr($e, -1, 1) != '"')
					{
						// $val is where we'll store the current string while we work on it
						$val = $e;
						// Set j to be the next value in the array
						$j = $i+1;
						// Move through the rest of the array until we find the string that closes the quote
						for($j;$j<count($exp);$j++)
						{
							// Value at point $exp[$j]
							$f = $exp[$j];

							// Is this the end of the string?
							// Closing quote
							if(substr($f, -1) == '"')
							{
								// Was the quote escaped?
								if(substr($f, -2) == '\"')
								{
									// Remove the escape characters
									$val .= " " . str_replace('\\', "", $f);
								}
								else
								{
									$val .= " $f";
									// Subtract 2 since length isn't base-0 and we want to remove the last quotation mark
									$params[] = substr($val, 1, strlen($val)-2); // Get rid of the quotation marks around the string
									// Let $i pick up where we left off
									$i = $j;
									// Kill this for loop
									break;
								}
							}
							else
							{
								$val .= " $f";
							}
						}

						if($j == count($exp))
						{
							// If $j == length of $exp, the quote never closed
							// Thus, we have an error
							return false;
						}
					}
					else
					{
						// Strip the quotes and use this value
						$params[] = substr($e, 1, strlen($e)-2);
					}
				}
				else
				{
					$params[] = $e;
				}
			}

			return $params;
		}
		else {
			return false;
		}
	}

	/**
	 * log()
	 * Logs the message in the database
	 *
	 * @author Cory Gehr
	 * @access public
	 */
	public function log()
	{
		global $_DB;

		// Add the user to the database if they don't already exist-
		// Or, update their name
		$query = "SELECT name
				  FROM users 
				  WHERE user_id = :user 
				  LIMIT 1";
		$params = array(':user' => $this->user_id);

		$result = $_DB['botstore']->doQueryAns($query, $params);

		if(!$result) {
			// Add user to database
			$query = "CALL insert_user(:id, :name, :group)";
			$params = array(':id' => $this->user_id, ':name' => $this->name, ':group' => $this->group_id);
			$_DB['botstore']->doQuery($query, $params);
		}
		elseif($result != $this->name)
		{
			$query = "UPDATE users SET name = :name WHERE user_id = :id LIMIT 1";
			$params = array(':name' => $this->name, ':id' => $this->user_id);
			$_DB['botstore']->doQuery($query, $params);
		}

		// Ensure user is in the group specified
		$query = "SELECT nickname
				  FROM users_groups 
				  WHERE user_id = :user 
				  AND group_id = :group 
				  LIMIT 1";
		$params = array(':user' => $this->user_id, ':group' => $this->group_id);

		$result = $_DB['botstore']->doQueryAns($query, $params);

		if(!$result && $this->sender_type != "bot" && $this->sender_type != "system")
		{
			// Insert into users_groups
			$query = "INSERT INTO users_groups(user_id, group_id, nickname)
					  VALUES(:user, :group, :name)";
			$params = array(':user' => $this->user_id, ':group' => $this->group_id, ':name' => $this->name);
			$_DB['botstore']->doQuery($query, $params);
		}
		elseif($result != $this->name)
		{
			// Nickname doesn't match, update it
			$query = "UPDATE users_groups SET nickname = :name WHERE user_id = :user AND group_id = :group LIMIT 1";
			$params = array(':name' => $this->name, ':user' => $this->user_id, ':group' => $this->group_id);
			$_DB['botstore']->doQuery($query, $params);
		}

		// Add the message to the database
		$query = "INSERT INTO messages(attachments, avatar_url, created_at, group_id, 
				  id, name, sender_id, sender_type, source_guid, system, text, user_id,
				  detected_commands, mentioned_users, attachment_ids)
				  VALUES(:attachments, :avatar_url, :created_at, :group_id, :id, :name, 
				  :sender_id, :sender_type, :source_guid, :system, :text, :user_id, 
				  :detected_commands, :mentioned_users, :attachment_ids)";

		$params = array(
			':attachments' => json_encode($this->attachments),
			':avatar_url' => $this->avatar_url,
			':created_at' => $this->created_at,
			':group_id' => $this->group_id,
			':id' => $this->id,
			':name' => $this->name, 
			':sender_id' => $this->sender_id,
			':sender_type' => $this->sender_type,
			':source_guid' => $this->source_guid,
			':system' => $this->system,
			':text' => $this->text,
			':user_id' => $this->user_id,
			':detected_commands' => json_encode($this->detected_commands),
			':mentioned_users' => json_encode($this->mentioned_users),
			':attachment_ids' => json_encode($this->attachment_ids)
		);

		return $_DB['botstore']->doQuery($query, $params);
	}

	/**
	 * message_contains()
	 * Returns true if the message contains a specific string
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param value String to search for
	 * @param case Case Sensitive Search (default: false)
	 * @return True if Yes, False if No
	 */
	public function message_contains($value, $case = false)
	{
		$haystack = $this->text;
		$needle = $value;

		// Convert strings to lowercase if case sensitivity isn't an issue
		// Makes searching easier
		if(!$case) {
			$haystack = strtolower($haystack);
			$needle = strtolower($needle);
		}

		return (strstr($haystack, $needle) != null);
	}

	/**
	 * parse()
	 * Parse a request from GroupMe
	 *
	 * @author Cory Gehr
	 * @access public
	 */
	public function parse()
	{
		global $_DB;

		// Get values from php://input
		$json = file_get_contents('php://input');
		$obj = json_decode($json, true);

		$this->attachments = $obj['attachments'];
		$this->avatar_url = $obj['avatar_url'];
		$this->created_at = $obj['created_at'];
		$this->group_id = $obj['group_id'];
		$this->id =  $obj['id'];
		$this->name = $obj['name'];
		$this->sender_id = $obj['sender_id'];
		$this->sender_type = $obj['sender_type'];
		$this->source_guid = $obj['source_guid'];
		$this->system = $obj['system'];
		$this->text = $obj['text'];
		$this->user_id = $obj['user_id'];

		// System causes issues... see if it's null
		if(!$this->system) {
			$this->system = null;
		}

		// Set received to true so we know there was an active request
		if($this->id) {
			$this->received = true;
		}

		// Parse rest of request so handlers can work with it

		// Detect any commands in the text
		if($this->contains_command()) {
			$this->detected_commands = array($this->get_command(false));
		}

		// If there are attachments, store those/get their IDs
		if($this->attachments) {
			foreach($this->attachments as $a) {
				if($a['type'] == "image") {
					$url = $a['url'];

					// Store image if we don't already have it in the DB
					$query = "CALL image_attached(:url, :group)";
					$params = array(':url' => $url, ':group' => $this->group_id);

					// Sproc checks if image exists, and inserts rows if not
					$this->attachment_ids[] = array('image' => ($_DB['botstore']->doQueryAns($query, $params)));
				}
				elseif($a['type'] == "location") {
					$name = $a['name'];
					$lat = $a['lat'];
					$lng = $a['lng'];

					// Add attachment
					$query = "CALL location_attached(:name, :lat, :lng, :group)";
					$params = array(':name' => $name, ':lat' => $lat, ':lng' => $lng, ':group' => $this->group_id);

					// Sproc adds location
					$this->attachment_ids[] = array('image' => ($_DB['botstore']->doQueryAns($query, $params)));
				}
				elseif($a['type'] == "mentions") {
					$this->mentioned_users = $a['user_ids'];
				}
				else {
					$query = "CALL unknown_attached(:type, :group)";
					$params = array(':type' => $a['type'], ':group' => $this->group_id);

					// Sproc adds unknown attachment
					$this->attachment_ids[] = array($a['type'] => ($_DB['botstore']->doQueryAns($query, $params)));
				}
			}
		}
	}

	/** 
	 * request_processed()
	 * Tells us if there was a request we could process
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return True if Yes, False if No
	 */
	public function request_processed()
	{
		return $this->received;
	}
}