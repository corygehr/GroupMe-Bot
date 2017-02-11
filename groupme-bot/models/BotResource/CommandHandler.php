<?php
	/**
	 * models/BotResource/CommandHandler.php 
	 * Contains the CommandHandler class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class CommandHandler extends \Thinker\Framework\Model
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
		
		// Gather commands used in message
		$command = $post->get_command();

		// Search for command in database
		$query = "SELECT method
				  FROM action_aliases aa 
				  JOIN actions a ON a.action_id = aa.action_id 
				  WHERE aa.alias = :alias 
				  LIMIT 1";
		$params = array(':alias' => $command);

		$result = $_DB['botstore']->doQueryAns($query, $params);

		if(method_exists($this, $result))
		{
			// Call method
			$this->{$result}($command, $post);
		}
		// If the method doesn't exist, ignore the message
	}

	// All functions should accept the command used to access it, and the CallbackPost object

	/**
	 * about()
	 * Displays the 'about' message
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function about($command, $post)
	{
		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$about = "SameBot was forged in a top secret laboratory by a premiere group of the world's most renouned scientists to combine the latest and greatest military-grade advancements in artificial intelligence into a ... haha nah just fuckin' with you Cory did this on a weekend when he had nothing better to do. Yell at him if something breaks. (╯°□°）╯︵ ┻━┻ ┬──┬";

		$message->text = $about;
		$message->send();
	}

	/**
	 * add_image_tag()
	 * Adds a new image tag to the database
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function add_image_tag($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$response = "";

		// Get the parameters associated with this command
		$cmd_params = $post->get_command_parameters();

		// Ensure we have the correct number of parameters
		if(count($cmd_params) != 2)
		{
			$response = "I didn't find the right number of parameters that time, try again. For help, enter '" . $post->command_notation . "help addtag'.";
		}
		else
		{
			// Parse tags
			$tag = strtolower($cmd_params[0]);
			$description = $cmd_params[1];

			// Ensure tag/alias doesn't already exist
			$query = "SELECT COUNT(1)
					  FROM action_aliases 
					  WHERE alias = :tag 
					  AND (group_id = :group OR group_id IS NULL)
					  LIMIT 1";
			$params = array(':tag' => $tag, ':group' => $post->group_id);

			if(!$_DB['botstore']->doQueryAns($query, $params))
			{
				// Add tag to database
				$query = "CALL insert_tag(:tag, :description, :group)";
				$params = array(':tag' => $tag, ':description' => $description, ':group' => $post->group_id);

				if($_DB['botstore']->doQuery($query, $params))
				{
					$response = "I added '$tag' and it's ready to use!";
				}
				else
				{
					$response = "I ran into a problem adding '$tag'. Try again.";
				}
			}
			else
			{
				$response = "Sorry, either a tag or command already exists by that name.";
			}
		}

		$message->text = $response;
		$message->send();
	}

	/**
	 * add_task()
	 * Adds a new task to be run in the requesting group
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function add_task($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$response = "";

		// Get parameters
		$cmd_params = $post->get_command_parameters();

		if(count($cmd_params) >= 4)
		{
			$start_date = $cmd_params[0];
			$end_date = $cmd_params[1];
			$interval = $cmd_params[2];
			$action = $cmd_params[3];
			$params = null;

			if(array_key_exists(4, $cmd_params))
			{
				$params = $cmd_params[4];
			}

			// Verify action exists by getting its ID
			$query = "SELECT action_id 
					  FROM action_aliases 
					  WHERE alias = :action 
					  LIMIT 1";
			$params = array(':action' => $action);

			$action_id = $_DB['botstore']->doQueryAns($query, $params);

			if($action_id)
			{
				// Parse responses
				$start_date = date('Y-m-d H:i:s', strtotime($start_date));
				$end_date = date('Y-m-d H:i:s', strtotime($end_date));

				// Add to database
				$query = "CALL insert_task(:group, :startdate, :enddate, :intervaltype, :interval, :actionid, :params)";
				$params = array(':group' => $post->group_id, ':startdate' => $start_date, ':enddate' => $end_date, 
								':intervaltype' => $interval_type, ':interval' => $interval, ':actionid' => $action_id,
								':params' => $params);

				if($_DB['botstore']->doQuery($query, $params))
				{
					$response = "The task has been created and will kick-off at $start_date!";
				}
				else
				{
					$response = "I couldn't add the task... I have failed you :(. Please try again.";
				}
			}
			else
			{
				$response = "The action you specified does not exist. Please check your query and try again.";
			}
		}
		else
		{
			$response = "I didn't get enough parameters to add a new task. For help, try " . $post->command_notation . "help addtask.";
		}

		$message->text = $response;
		$message->send();
	}

	/**
	 * commands()
	 * Displays a list of all commands supported
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function commands($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$query = "SELECT action 
				  FROM actions 
				  ORDER BY action";
		$results = $_DB['botstore']->doQueryArr($query);

		$command_list = "**NOTE: Tags are not included in this list** All commands:";

		if($results) {
			foreach($results as $r) {
				$command_list .= " " . $r['action'];
			}
		}

		$message->text = $command_list;
		$message->send();
	}

	/**
         * giphy_post()
	 * Displays a GIF from Giphy
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallBackPost object containing the original message
	 */
	private function giphy_post($command, $post)
	{
		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);
		
		// Get the parameters associated with this command
		$cmd_params = $post->get_command_parameters();
		
		// Expecting only one parameter
		if(count($cmd_params) == 0 || count($cmd_params) > 1) {
	            // Too many or two few parameters
		    $message->text = "I didn't get right number of parameters! For help, try " . $post->command_notation . "help giphy.";
		}
		else {
		    // Correct number of parameters
		    // Get GIPHY API URL and Key
		    $query = "CALL get_global('GIPHY_API_KEY')";
		    $api_key = $_DB['botstore']->doQueryAns($query);
		    $query = "CALL get_global('GIPHY_API_URL')";
		    $giphy_url = $_DB['botstore']->doQueryAns($query);
		    $query = "CALL get_global('API_ACCESS_TOKEN')";
		    $access_token = $_DB['botstore']->doQueryAns($query);
			
		    // Ensure we have an API key and URL
		    if($api_key && $giphy_url && $access_token) {
			// Replace tag spaces with '+' for proper URL formatting
			$tag = str_replace(" ", "+", $cmd_params[0]);
			    
			// Create request to get an image
			$giphy_url .= "/random?api_key=$api_key&tag=$tag";
			    
			// Set headers
			$options = array(
			    'http' => array(
			        'header'  => "Content-type: application/json\r\n",
			        'method'  => 'GET'
			    )
			);
			
			// Open stream
			$context = stream_context_create($options);
			$result = file_get_contents($giphy_url, false, $context);
			
			// Parse result
			$details = json_decode($result, true);
			    
			// Check for success
			if(array_key_exists("data", $details) && array_key_exists("image_url", $details["data"])) {
			    // Download image to temporary directory (so we can upload to GroupMe - required)
			    file_put_contents("tmpgif.gif", fopen($details["data"]["image_url"], 'r'));
		            // Get base64 encoding of GIF
			    $img_data = base64_encode(file_get_contents("tmpgif.gif"));
		            // POST to GroupMe API
			    // Set headers
			    $options = array(
			    'http' => array(
				'header'  => "Content-type: application/json\r\n" .
				             "X-Access-Token: $access_token",
				'method'  => 'POST',
				'content' => $img_data
			        )
			    );

			    // Open stream
			    $context = stream_context_create($options);
			    $result = file_get_contents("https://image.groupme.com/pictures", false, $context);
				
			    // Get result URL
			    $result_json = json_decode($result, true);
				
			    // Ensure we got a result
			    if($result_json && array_contains_key("payload", $result_json) && array_contains_key("image_url", $result_json["payload"])) {
				// Create a new image attachment and execute
				$attachment = new Image();
				$attachment->url = $result_json["payload"]["image_url"];
				$message->add_attachment($attachment);
			    }
			    else {
				$message->text = "I had an issue getting GroupMe to play along. Try again?";    
			    }
			}
			else {
			    $message->text = "Sorry! I didn't get a response for that tag.";	
			}
		    }
		    else {
			$message->text = "I don't have what I need to talk to Giphy... yell at Cory.";
		    }
		}
		
		// Send message
		$message->send();
	}
	
	/**
	 * help()
	 * Displays a help message with the specified parameters
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function help($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		// Get the parameters associated with this command
		$cmd_params = $post->get_command_parameters();

		if(count($cmd_params) == 0) {
			$message->text = "help help help (But actually, if you need help try '" . $post->command_notation . "help {command}' without the brackets).";
		}
		else if(count($cmd_params) > 1) {
			$message->text = "'help' is used by typing '" . $post->command_notation . "help {command}' without the brackets. Only one parameter can be used.";
		}
		else {
			// First param is always the command we want help with
			// Strip the command notation if it's included
			$help_cmd = str_replace($post->command_notation, "", array_shift($cmd_params));

			// Lookup help text for the command
			$query = "SELECT aa.alias, aa.description, a.usage 
					  FROM action_aliases aa 
					  JOIN actions a ON a.action_id = aa.action_id 
					  WHERE aa.alias = :cmd 
					  AND (aa.group_id = :group OR aa.group_id IS NULL) 
					  LIMIT 1";
			$params = array(':cmd' => $help_cmd, ':group' => $post->group_id);

			$result = $_DB['botstore']->doQueryOne($query, $params);

			if($result) {
				// Compile help text
				$help_text = $result['alias'] . ": " . $result['description'] . " -Usage: " . $post->command_notation . $result['usage'];
				$message->text = $help_text;
			}
			// Command doesn't exist, let's see if we're being playful
			elseif($help_cmd == "me") {
				$message->text = "There is no help for you.";
			}
			else {
				$message->text = "Sorry, either that command doesn't exist or no description is available. ¯\_(ツ)_/¯";
			}
		}

		$message->send();
	}

	/**
	 * image_tag_stats()
	 * Outputs the number of items listed under an image tag
	 * 
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function image_tag_stats($command, $post)
	{
		global $_DB;
		
		// Get params
		$cmd_params = $post->get_command_parameters();
		
		// Create response object
		$message = new CallbackResponse($post->group_id);
		$response = "";
		
		// Verify correct param count
		if(count($cmd_params) == 1)
		{
			$tag = $cmd_params[0];
		
			// Get count of images stored under the selected tag
			$query = "SELECT COUNT(1)
				  FROM attachment_tags at 
				  JOIN tags t ON t.tag_id = at.tag_id 
				  WHERE t.group_id = :group
				  AND t.tag = :tag";
			$params = array(':group' => $post->group_id, ':tag' => $tag);
			
			$img_count = $_DB['botstore']->doQueryAns($query, $params);
			
			if($img_count <= 0)
			{
				$response = "I don't have any images under '$tag'. If the tag exists, start tagging them using " . $post->command_notation . "tag!";
			}
			else
			{
				// Get number of times the tag has been used
				$query = "SELECT COUNT(1)
					  FROM messages 
					  WHERE group_id = :group 
					  AND detected_commands LIKE :command
					  LIMIT 1";
				$params = array(':group' => $post->group_id, ':command' => '%"'.$tag.'"%');
				
				$use_count = $_DB['botstore']->doQueryAns($query, $params);
				
				$response = "I have " . $img_count . " image(s) under '$tag'. The tag has been used $use_count time(s).";
			}	
		}
		else
		{
			$response = "I didn't get right number of parameters! For help, try " . $post->command_notation . "tagstats {tagname}.";
		}
		
		// Send message
		$message->text = $response;
		$message->send();
	}

	/**
	 * list_group_tasks()
	 * Lists all tasks for the requesting group
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function list_group_tasks($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		// Get all tasks for the current group
		$query = "SELECT t.task_id, t.start_date, t.end_date, a.action, t.params, 
				  t.repeat_interval_type, t.repeat_interval_value
				  FROM tasks t 
				  JOIN actions a ON t.action_id = a.action_id 
				  WHERE t.group_id = :group 
				  AND t.end_date > NOW() 
				  ORDER BY t.task_id";
		$params = array(':group' => $post->group_id);

		$results = $_DB['botstore']->doQueryArr($query, $params);

		$response = "";

		if($result)
		{
			$response = "Format: ID, Start Date, End Date, Action, Params, Repeat Interval
			";
			// Add tasks to string
			foreach($result as $r)
			{
				$response .= $r['task_id'] . ", " . $r['start_date'] . ", " . $r['end_date'] . ", " . $r['action'] . ", " . $r['params'] . ", " . $r['repeat_interval_type'] . $r['repeat_interval_value'] . "
				";
			}
		}
		else
		{
			$response = "I don't have any tasks set for your group! Get started using " . $post->command_notation . "addtask!";
		}

		$message->text = $response;
		$message->send();
	}

	/**
	 * list_image_tags()
	 * Lists all tags for the requesting group
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function list_image_tags($command, $post)
	{
		global $_DB;

		$tag_list = "";

		// Get all tags for the current group
		$query = "SELECT tag 
				  FROM tags 
				  WHERE group_id = :group 
				  ORDER BY tag";
		$params = array(':group' => $post->group_id);

		$result = $_DB['botstore']->doQueryArr($query, $params);

		if($result) {
			$tag_list = "I found " . count($result) . " tag(s) for your group:";
			foreach($result as $r) {
				$tag_list .= " " . $r['tag'];
			}
		}
		else {
			$tag_list = "I didn't find any tags for your group! Try 'addtag' to get started.";
		}

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$message->text = $tag_list;
		$message->send();
	}

	/**
	 * random_image_by_tag()
	 * Displays a random image based on the specified tag
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function random_image_by_tag($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$tag = $command;

		// If $command is the function name, we need to get the first param element
		if($command == "randomimg") {
			$cmd_params = $post->get_command_parameters();

			if(count($cmd_params) != 1) {
				$message->text = "That's not the right number of parameters for this command. Try '" . $post->command_notation . "help randomimg' for help.";
				$message->send();
				return;
			}
			else {
				// Set 'command' to the parameter so we pull the correct tag
				$tag = $cmd_params[0];
			}
		}

		// Make tag lowercase...
		$tag = strtolower($tag);

		// Get all images for this tag
		$query = "SELECT i.url, i.caption 
				  FROM images i
				  JOIN attachments a ON a.attachment_id = i.attachment_id
				  JOIN attachment_tags at ON at.attachment_id = i.attachment_id 
				  JOIN tags t ON t.tag_id = at.tag_id 
				  WHERE t.tag = :tag 
				  AND t.group_id = :group 
				  AND a.group_id = :group";
		$params = array(':tag' => $tag, ':group' => $post->group_id);

		$results = $_DB['botstore']->doQueryArr($query, $params);

		if($results) {
			// Pick a random image from that group
			$last_index = count($results)-1;
			$random_index = rand(0, $last_index);

			// Create a new image attachment
			$attachment = new Image();

			$attachment->url = $results[$random_index]['url'];

			$message->add_attachment($attachment);
			$message->text = $results[$random_index]['caption'];
		}
		else
		{
			$message->text = "I can't find any images tagged under '$tag'! Why not add some? Try " . $post->command_notation . "help tag for more info.";
		}

		$message->send();
	}

	/**
	 * snooze()
	 * Pauses the bot for a set duration of time
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function snooze($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		// Get the snooze interval
		$cmd_params = $post->get_command_parameters();

		$response = "";

		if(count($cmd_params) != 1) {
			$response = "There were too many parameters in that command! For help, try " . $post->command_notation . "help snooze.";
		}
		else {
			$wait_mins = $cmd_params[0];

			$query = "CALL start_snooze(:group, :interval_mins)";
			$params = array(':group' => $post->group_id, ':interval_mins' => $wait_mins);

			$result = $_DB['botstore']->doQueryAns($query, $params);

			if($result) {
				$wake_time = date('H:i', strtotime($result));
				$wake_day = date('F j', strtotime($result));
				$response = "I'll wake up at $wake_time on $wake_day (UTC). To wake me sooner, just enter " . $post->command_notation . "wake.";
			}
			else {
				$response = "Something went wrong... yell at Cory. I'm just doing things how he tells me to.";
			}
		}

		$message->text = $response;
		$message->send();
	}

	/**
	 * stop_task()
	 * Stops a task from running
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function stop_task($command, $post)
	{
		global $_DB;

		// Create the response object
		$message = new CallbackResponse($post->group_id);

		$response = "";

		// Get Task ID
		$cmd_params = $post->get_command_parameters();

		if(count($cmd_params) == 1)
		{
			$task_id = $cmd_params[0];

			// Call query
			$query = "CALL stop_task(:task, :group)";
			$params = array(':task' => $task_id, ':group' => $post->group_id);

			if($_DB['botstore']->doQuery($query, $params))
			{
				$response = "I've stopped Task $task_id. To start it again, you'll need to recreate it using 'addtask'.";
			}
			else
			{
				$response = "I didn't find a task with that ID that I could stop, try again.";
			}
		}
		elseif(count($cmd_params) < 1)
		{
			$response = "I didn't get enough parameters for that command. For help, try " . $post->command_notation . "help stoptask";
		}
		else
		{
			$response = "I saw more parameters than I was expecting. For help, try " . $post->command_notation . "help stoptask";
		}

		$message->text = $response;
		$message->send();
	}

	/**
	 * sunshine_display()
	 * Gets an unread sunshines and sends it to the group
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function sunshine_display($command, $post)
	{
		global $_DB;

		// Create the response object
		$message = new CallbackResponse($post->group_id);
		$response = "";

		// Get a random sunshine for the group that hasn't been displayed yet
		$query = "SELECT sunshine_id, message
				  FROM sunshine_queue 
				  WHERE group_id = :groupId 
				  AND displayed IS NULL 
				  ORDER BY RAND() 
				  LIMIT 1";
		$params = array(':groupId' => $post->group_id);

		$result = $_DB['botstore']->doQueryOne($query, $params);

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

			// Set response to the message text
			$response = 'Sunshine! "' . $result['message'] . '"';
		}
		else
		{
			// Do we send an error?
			$cmd_params = $post->get_command_parameters();

			if(count($cmd_params) >= 1)
			{
				// Don't display an error, I know the directions are to say 'silent'
				// But realistically if any params are sent we don't want to bug the 
				// group if there was a typo
				return;
			}
			else
			{
				// Send an error response
				$response = "There aren't any sunshines in the queue! Submit some at https://groupme.corygehr.com/Sunshines/add.";
			}
		}

		if(!empty($response))
		{
			// Write the message text and send it
			$message->text = $response;
			$message->send();
		}
	}

	/**
	 * tag_image()
	 * Tags an image
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function tag_image($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$response = "";

		// Get the parameters
		$cmd_params = $post->get_command_parameters();

		if($cmd_params < 1) {
			$response = "You're missing a tag! Try again.";
		}
		else {
			$tag = strtolower($cmd_params[0]);

			// Ensure tag exists
			$query = "SELECT tag_id 
					  FROM tags 
					  WHERE group_id = :group 
					  AND tag = :tag 
					  LIMIT 1";
			$params = array(':group' => $post->group_id, ':tag' => $tag);

			if(!$_DB['botstore']->doQueryAns($query, $params)) {
				$response = "'$tag' isn't a valid tag. Try again!";
			}
			else {
				// Get the attachment(s) that came with this post, if any
				if(!empty($post->attachment_ids)) {
					// Pull the data about the attached images

					// Find all image attachments
					$err = false;

					foreach($post->attachment_ids as $aid) {
						// Only include image types...
						if(array_key_exists("image", $aid)) {
							$query = "CALL tag_image(:aid, :tag, :group)";
							$params = array(':aid' => $aid["image"], ':tag' => $tag, ':group' => $post->group_id);

							if(!$_DB['botstore']->doQuery($query, $params)) {
								// An error came up... fail
								$err = true; 
								break;
							}
						}
					}

					if($err) {
						$response = "One or more of the images you posted couldn't be tagged. Sorry about that.";
					}
					else {
						$response = "Tagged the image(s) under '$tag'.";
					}
				}
				else {
					// Look through user's message history
					// Limit to past five posts, within five minutes
					$query = "SELECT attachment_ids 
							  FROM messages 
							  WHERE user_id = :user 
							  AND group_id = :group 
							  AND timestamp > (NOW() - INTERVAL 5 MINUTE)
							  ORDER BY created_at DESC";
					$params = array(':user' => $post->user_id, ':group' => $post->group_id);

					$results = $_DB['botstore']->doQueryArr($query, $params);

					if($results) {
						foreach($results as $r) {
							// Check for attachment ids with image
							// If they exist, we know an image was submitted
							// Due to ordering we know it'll only tag the most recent image
							$aids = json_decode($r['attachment_ids'], true);
							$terminate = false;

							if(count($aids) > 0) {
								// Grab all image IDs
								foreach($aids as $a) {
									if(array_key_exists("image", $a)) {
										// We found images
										$terminate = true; // Finish after adding all images from this array
										$query = "CALL tag_image(:aid, :tag, :group)";
										$params = array(':aid' => $a["image"], ':tag' => $tag, ':group' => $post->group_id);

										if(!$_DB['botstore']->doQuery($query, $params)) {
											// An error came up... fail
											$err = true;
											break;
										}
									}
								}
							}

							if($terminate) {
								if($err) {
									$response = "One or more of the images you posted couldn't be tagged. Sorry about that.";
								}
								else {
									$response = "Tagged the image(s) under '$tag'.";
								}
								break;
							}
							else {
								$response = "You don't seem to have posted any images in the past five minutes. I just did all that work for nothing...";
							}
						}
					}
					else {
						$response = "You haven't posted any images recently to tag. I'll only go back and tag the last image you posted if it was submitted up to five minutes ago. Sorry, I'm needy.";
					}
				}
			}
		}

		// Send message
		$message->text = $response;
		$message->send();
	}

	/**
	 * toggle_group_lock()
	 * Toggles the group protection mechanism
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function toggle_group_lock($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$response = "";

		// Get the parameters
		$cmd_params = $post->get_command_parameters();

		if(count($cmd_params) == 1)
		{
			$toggleMode = strtolower($cmd_params[0]);

			if($toggleMode == 'on')
			{
				// Get list of current members
				$api = new APIWrapper($post->group_id);

				$list = $api->group_list_members($post->group_id);

				$query = "UPDATE groups 
						  SET membership_locked = 1 
						  WHERE group_id = :groupid 
						  LIMIT 1";
				$params = array(':groupid' => $post->group_id);

				if($_DB['botstore']->doQuery($query, $params))
				{
					$response = "Your group is no longer allowing new members. Current members are... " + print_r($list, true);
				}
				else
				{
					$response = "This is awkward... I ran into a problem updating the group setting. Try again?";
				}
			}
			elseif($toggleMode == 'off')
			{
				$query = "UPDATE groups 
						  SET membership_locked = 0 
						  WHERE group_Id = :groupid 
						  LIMIT 1";
				$params = array(':groupid' => $post->group_id);

				if($_DB['botstore']->doQuery($query, $params))
				{
					$response = "Your group is now open to new members.";
				}
				else
				{
					$response = "This is awkward... I ran into a problem updating the group setting. Try again?";
				}
			}
			else
			{
				// Invalid parameter
				$response = "I didn't understand " . $toggleMode . ". Try again or ask for /help.";
			}
		}
		elseif(count($cmd_params) > 1)
		{
			$response = "That's too many parameters! Try again or ask for /help.";
		}
		else
		{
			// No params means we need to get the status
			$query = "CALL group_locked(:groupid)";
			$params = array(':groupid' => $post->group_id);

			$response = "Group protection is currently ";

			// Change response based on the returned value (1 = yes, 0 = no)
			if($_DB['botstore']->doQueryAns($query, $params))
			{
				$response .= "ENABLED. Any users added to the group will be kicked immediately.";
			}
			else
			{
				$response .= "DISABLED. Any user can be added to the group.";
			}
		}

		// Send message
		$message->text = $response;
		$message->send();
	}

	/**
	 * wake()
	 * Wake the bot immediately
	 *
	 * @author Cory Gehr
	 * @access private
	 * @param command Command used to call this method
	 * @param post CallbackPost object containing the original message
	 */
	private function wake($command, $post)
	{
		global $_DB;

		// Create the CallbackResponse
		$message = new CallbackResponse($post->group_id);

		$response = "";

		// Check if we're snoozing
		$query = "CALL snoozing(:group)";
		$params = array(':group' => $post->group_id);

		if($_DB['botstore']->doQueryAns($query, $params)) {
			// Wake
			$query = "CALL wake(:group)";

			if($_DB['botstore']->doQuery($query, $params)) {
				$response = "Ugh... only if you grab me coffee.";
			}
			// We won't respond if there's an issue...
		}
		else {
			$response = "I'm already awake, but thanks for checking on me!";
		}

		$message->text = $response;
		$message->send();
	}
}
