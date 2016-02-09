<?php
	/**
	 * models/BotResource/CallbackResponse.php 
	 * Contains the CallbackResponse class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class CallbackResponse extends \Thinker\Framework\Model
{
	// See https://dev.groupme.com/tutorials/bots
	public $text;
	private $attachments;
	private $mentions;
	private $api_url;
	private $bot_id;
	private $group_id;
	private $sent;

	/**
	 * __construct()
	 * Constructor for the CallbackResponse class
	 *
	 * @author Cory Gehr
	 * @access public
	 * @
	 */
	public function __construct($group_id)
	{
		global $_DB;

		$this->group_id = $group_id;

		// Get globals
		$query = "CALL get_global('API_POST_URL')";
		$this->api_url = $_DB['botstore']->doQueryAns($query);

		// Get Bot ID for the sending group
		$query = "SELECT bot_id
				  FROM groups 
				  WHERE group_id = :group 
				  LIMIT 1";
		$params = array(':group' => $group_id);

		$bot_id = $_DB['botstore']->doQueryAns($query, $params);

		if($bot_id)
		{
			$this->bot_id = $bot_id;
		}
		// Message will not send if no bot_id exists per send()

		// Prepare for attachments
		$this->attachments = array();

		// Reset received notice
		$this->sent = false;
	}

	/**
	 * add_attachment()
	 * Adds a new attachment to the message
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param Attachment item Item to be attached
	 */
	public function add_attachment(Attachment $item)
	{
		$this->attachments[] = $item;
	}

	/**
	 * compile_message()
	 * Compiles the message to be sent
	 *
	 * @author Cory Gehr
	 * @access private
	 * @return Array with message data
	 */
	public function compile_message()
	{
		if($this->bot_id)
		{
			$data = array(
				"bot_id" => $this->bot_id,
				"text" => $this->text
			);

			// If we have any mentions, we need to compile the first
			if($this->mentions)
			{
				// Add the message being sent to the mention
				$this->mentions->add_message_string($this->text);

				// Add the mention object to 'attachments'
				$this->add_attachment($this->mentions);
			}

			// Check if we have any attachments
			if(!empty($this->attachments))
			{
				$attach = array();
				
				foreach($this->attachments as $a)
				{
					// Add attributes to 'attachments'
					$attach[] = $a->compile_attributes();
				}

				$data += array(
					"attachments" => $attach
				);
			}

			return $data;
		}
		else
		{
			return false;
		}
	}

	/**
	 * mention_user()
	 * Adds a user to be mentioned
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return False if no message exists
	 */
	public function mention_user($id)
	{
		// Create mentions object if it doesn't already exist
		if(!$this->mentions)
		{
			$this->mentions = new Mention($this->group_id);
		}

		// Return the result of adding a user to the mention
		return $this->mentions->add_user($id);
	}

	/**
	 * message_sent()
	 * Tells if this message has been sent to the GroupMe API
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return True if Yes, False if No
	 */
	public function message_sent()
	{
		return $this->sent;
	}

	/**
	 * send()
	 * Sends the message to the GroupMe API
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return True on Success, False on Failure
	 */
	public function send()
	{
		// Send only if message hasn't been sent yet
		if(!$this->sent && $this->bot_id)
		{
			// Send message to GroupMe API
			$options = array(
			    'http' => array(
			        'header'  => "Content-type: application/json\r\n",
			        'method'  => 'POST',
			        'content' => json_encode($this->compile_message()),
			    ),
			);
			
			$context = stream_context_create($options);
			$result = file_get_contents($this->api_url, false, $context);

			$this->sent = true;
			return $this->sent;
		}
		else
		{
			return false;
		}
	}
}