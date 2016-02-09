<?php
	/**
	 * models/BotResource/BotMentionHandler.php 
	 * Contains the BotMentionHandler class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class BotMentionHandler extends \Thinker\Framework\Model
{
	/** 
	 * process_message()
	 * Processes the message
	 *
	 * @access public
	 */
	public function process_message($post)
	{
		// Parse message text
		$breakdown = explode(" ", $post);

		// Look for SameBot references
		$spotted = array();
		
		for($i=0; $i<count($breakdown); $i++)
		{
			$word = $breakdown($i);

			if(strtolower($word) == "samebot")
			{
				$spotted[] = $i;
			}
		}

		// Continue processing if it was found
		if(!empty($spotted))
		{
			// We're probably sending a message
			$message = new CallbackResponse($post->group_id);

			// Check the context of the word
			// For the first iteration of this, we're just going to use the first reference
			$ind = $spotted[0];

			// Store indices of positive or negative references
			$pos = array();
			$neg = array();

			// Look before the reference
			for($i=0;$i<$ind;$i++)
			{
				$curr_word = $breakdown[$i];
			}

			// Look after the reference
			for($i=$ind+1;$i<count($breakdown);$i++)
			{
				$curr_word = $breakdown[$i];
			}
		}
	}
}