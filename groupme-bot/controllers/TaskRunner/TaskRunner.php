<?php
	/**
	 * TaskRunner.php
	 * Contains the Class for the TaskRunner Controller
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot;

class TaskRunner extends \Thinker\Framework\Controller
{
	/**
	 * showSunshine()
	 * Runs the showSunshine routine
	 *
	 * @access public
	 */
	public function showSunshine()
	{
		global $_DB;

		// Is a GroupID specified?
		$group_id = \Thinker\Http\Request::get("group_id", true);

		if($group_id)
		{
			// Get a random sunshine for the group that hasn't been displayed yet
			$query = "SELECT sunshine_id, message
					  FROM sunshine_queue 
					  WHERE group_id = :groupId 
					  AND displayed IS NULL 
					  ORDER BY RAND() 
					  LIMIT 1";
			$params = array(':groupId' => $group_id);

			$result = $_DB['botstore']->doQueryOne($query, $params);

			// Continue if we have a result, do nothing if now
			if($result)
			{
				// Update sunshine as being displayed
				$query = "UPDATE sunshine_queue 
						  SET displayed = NOW() 
						  WHERE sunshine_id = :id 
						  LIMIT 1";
				$params = array(':id' => $result['sunshine_id']);

				// Execute query
				$_DB['botstore']->doQuery($query, $params);

				// Create the response object
				$message = new \GroupMeBot\BotResource\CallbackResponse($group_id);

				// Set response to the message text
				$message->text = 'New Sunshine! "' + $result['message'] + '"';

				// Send message
				$message->send();
			}
		}
		else
		{
			// 404
			\Thinker\Http\Redirect::error(404);
		}

		// Stop processing after this
		die();
	}
}
?>