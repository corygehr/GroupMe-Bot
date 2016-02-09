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
		global $_DB;
		
		// Determine the action that happened in the message
		if($post->message_contains("has joined the group") || $post->message_contains("has rejoined the group"))
		{
			// User has joined or rejoined the group, is the group under protection?
			$query = "CALL group_locked(:groupid)";
			$params = array(':groupid' => $post->group_id);

			if($_DB['botstore']->doQueryAns($query, $params))
			{
				// Check to see if the added user is recognized as a member of the group already
			}
			// If not, do nothing
		}
	}
}
