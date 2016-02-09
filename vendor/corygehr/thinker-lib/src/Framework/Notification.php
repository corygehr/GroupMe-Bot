<?php
	/**
	 * Notification.php
	 * Contains the Thinker\Framework\Notification class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\Framework;

class Notification {

	/**
	 * __construct()
	 * Constructor for the Thinker\Framework\Notification class
	 * This is intentionally private, so that it cannot be 
	 * 	instantiated
	 *
	 * @author Cory Gehr
	 * @access private
	 */
	private function __construct() {}

	/**
	 * push()
	 * Pushes a notification to the message cache
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $text Message Text
	 * @param string $level Message Level (info, error, warning, success) (default: info)
	 * @param boolean $overwrite Flag that would remove all other messages and add this one (default: false)
	 */
	public static function push($text, $level = 'info', $overwrite = false)
	{
		global $_MESSAGES;

		if($overwrite)
		{
			$_MESSAGES = array();
		}

		// Set message
		$_MESSAGES[] = array(
			0 => $text,
			1 => $level
			);
	}
}
?>