<?php
	/**
	 * GroupMeAuth.php
	 * Contains the Class for the GroupMeAuth Controller
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot;

class GroupMeAuth extends \Thinker\Framework\Controller
{
	/**
	 * receiveToken()
	 * Receives an API token from GroupMe's OAuth Service
	 *
	 * @access public
	 */
	public function receiveToken()
	{
		// Get token from URL
		$token = \Thinker\Http\Request::get("access_token");

		if($token)
		{
			// Process token
			if($this->process_token($token))
			{
				// Redirect to sunshine portal
				\Thinker\Http\Redirect::go("Sunshines", "add");
			}
			else
			{
				// 403 User Not Found
				\Thinker\Http\Redirect::error(403);
			}
		}
		else
		{
			// Throw an error since we didn't get a proper request
			\Thinker\Http\Redirect::error(404);
		}

		return true;
	}

	/**
	 * redirect()
	 * Passes data back for the 'redirect' subsection
	 *
	 * @access public
	 */
	public function redirect()
	{
		global $_DB;

		// Get GroupMe API url
		$query = "SELECT value
				  FROM globals 
				  WHERE name = 'OAUTH_CALLBACK' 
				  LIMIT 1";

		$url = $_DB['botstore']->doQueryAns($query, $params);

		if($url)
		{
			// Push user to GroupMe website for authentication
			header('Location: ' . $url);
			die();
		}
		else
		{
			// No URL? That's bad.
			\Thinker\Http\Redirect::error(500);
		}
	}

	/**
	 * process_token()
	 * Processes an OAuth Token from GroupMe
	 *
	 * @access private
	 * @param token Token value
	 * @return True on Success, False on Failure
	 */
	private function process_token($token)
	{
		global $_DB;

		if($token)
		{
			// Log in as the user with this token
			$query = "SELECT user_id, name 
					  FROM users 
					  WHERE access_token = :token 
					  LIMIT 1";
			$params = array(':token' => $token);

			$user = $_DB['botstore']->doQueryAns($query, $params);

			if($user)
			{
				// Set session variables
				$_SESSION['GROUPME_TOKEN'] = $token;
				$_SESSION['USER_ID'] = $user['user_id'];
				$_SESSION['USER_NAME'] = $user['name'];

				return true;
			}
			else
			{
				// Figure out who it is
				// Create an API Request
				$request = new APIRequest("/users/me", "GET", array("token" => $token));
				// Execute the request
				$user_data = $request->execute();

				// If we have data, add it to the user's entry
				if($user_data)
				{
					$query = "UPDATE users
							  SET access_token = :token 
							  WHERE user_id = :userId 
							  LIMIT 1";
					$params = array(':token' => $token, ':userId' => $user_data["response"]["id"]);

					$_DB['botstore']->doQuery($query, $params);

					// Set session variables and end sequence
					$_SESSION['GROUPME_TOKEN'] = $token;
					$_SESSION['USER_ID'] = $user_data["response"]["id"];
					$_SESSION['USER_NAME'] = $user_data["response"]["name"];

					return true;
				}
			}
		}

		return false;
	}
}
?>