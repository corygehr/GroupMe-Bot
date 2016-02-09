<?php
	/**
	 * Callback.php
	 * Contains the Class for the Callback Controller
	 *
	 * @author Cory Gehr
	 */
	
namespace GroupMeBot;

class Callback extends \Thinker\Framework\Controller
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
		return 'receive';
	}

	/**
	 * receive()
	 * Passes data back for the 'receive' subsection
	 *
	 * @access public
	 */
	public function receive()
	{
		// Create a CallbackPost object
		$callback_request = new BotResource\CallbackPost();
		$callback_request->parse();

		// Start our processing of the request
		if($callback_request->request_processed())
		{
			// Log the message
			$callback_request->log();

			// See if the bot is snoozing on this group...
			if($this->snoozing($callback_request->group_id))
			{
				// If the command isn't 'wake', kill the script
				if($callback_request->get_command() != "wake")
				{
					exit();
				}
			}

			// Don't send a response if we're just answering ourselves...
			if($callback_request->name != "SameBot")
			{
				// Detect switches that will return some sort of imagery
				if($callback_request->system_message())
				{
					// GroupMe system message
					$system_handler = new BotResource\SystemHandler();
					$system_handler->process_message($callback_request);
				}
				elseif($callback_request->contains_command())
				{
					// Create a new Command Handler object to handle the requested command
					$command_handler = new BotResource\CommandHandler();
					$command_handler->process_message($callback_request);
				}
				elseif($callback_request->message_contains("samebot"))
				{
					// Use the BotMention handler to determine if a message should be sent
					$botMention = new BotResource\BotMentionHandler($callback_request);
				}
			}
		}
		else
		{
			// No request, 404
			header('HTTP/1.0 404 Not Found');
			exit();
		}
	}

	/**
	 * snoozing()
	 * Returns true if the bot is snoozing on the current group
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param group_id Group ID
	 * @return True if snoozing, false if awake
	 */
	private function snoozing($group_id)
	{
		global $_DB;

		$query = "CALL snoozing(:group)";
		$params = array(':group' => $group_id);

		return $_DB['botstore']->doQueryAns($query, $params);
	}
}
?>