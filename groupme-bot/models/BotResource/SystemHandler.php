<?php
	/**
	 * models/BotResource/SystemHandler.php 
	 * Contains the SystemHandler class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class SystemHandler extends \Thinker\Framework\Model
{
	/**
	 * process_message()
	 * Processes the message
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param post CallbackPost object containing the original message
	 */
	public function process_message($post)
	{
		error_log("Processing System Event");
		// Determine the action that happened in the message
		if($post->message_contains("has joined the group") || $post->message_contains("has rejoined the group"))
		{
			error_log("User added!");
			// Handle action with member_added()
			$this->member_added($post);
		}
	}

	/**
	 * member_added()
	 * Handles the event where a member has been added to a group
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param post CallbackPost object containing the original message
	 */
	private function member_added($post)
	{
		global $_DB;

		// User has joined or rejoined the group, is the group under protection?
		$query = "CALL group_locked(:groupid)";
		$params = array(':groupid' => $post->group_id);

		// If a 1 is returned, the group is enforcing membership lock
		if($_DB['botstore']->doQueryAns($query, $params))
		{
			error_log("Group is locked");
			// Create the CallbackResponse
			$message = new CallbackResponse($post->group_id);
			$response = "";

			// Check to see if the added user is recognized as a member of the group already
			// To do this, we need to hit the GroupMe API with a user's Access Token
			$query = "CALL get_group_access_token(:groupid)";
			$params = array(':groupid' => $post->group_id);

			$token = $_DB['botstore']->doQueryAns($query, $params);

			// We have a token
			if($token)
			{
				// Cool! Let's get the list of current members in the group directly from GroupMe
				$request = new APIRequest("/groups/{$post->group_id}?token=$token", "GET");
				$result = $request->execute();

				// Check if we got the expected result
				if(array_key_exists('response', $result) && array_key_exists('members', $result['response']))
				{
					// Verify each user is currently a member per our database
					foreach($result["response"]["members"] as $mem)
					{
						// Declare local variables
						$user_id = $mem["user_id"]; // Global User ID
						$member_id = $mem["id"]; // Membership ID is unique to the user+group
						$name = $mem["nickname"]; // Global User Name

						// Call database record
						$query = "CALL user_in_group(:userid, :groupid)";
						$params = array(':userid' => $user_id, ':groupid' => $post->group_id);

						// If this block executes, the user was not listed in the group in our records
						if(!$_DB['botstore']->doQueryAns($query, $params))
						{
							// User is not in this group according to us. Get em out!
							$request = new APIRequest("/groups/{$post->group_id}/members/$mem_id/remove?token=$token", "POST");
							$removeResult = $request->execute();

							// Alert the group that someone was removed
							$message->text = print_r($removeResult, true) . ' | ' . "Tango down: " . $name;
							$message->send();
						}
					}
				}
				else
				{
					// We got an unexpected response...
					$message->text = "I couldn't reach GroupMe to get their membership records. Unfortunately that means I cannot determine if this person belongs here. Sorry!";
					$message->send();
					return;
				}
			}
			else
			{
				// We have no token - we can't do anything right now
				$message->text = "I don't have an access token for this group so I can't tell if this person belongs here. Your lack of preparation is to blame, shame on you.";
				$message->send();
				return;
			}
		}
	}
}
