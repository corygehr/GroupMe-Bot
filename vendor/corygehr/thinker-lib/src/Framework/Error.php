<?php
	/**
	 * Error.php
	 * Contains the Thinker\Framework\Error class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\Framework;

class Error {

	/**
	 * __construct()
	 * Constructor for the Thinker\Error class
	 * This is intentionally private, so that it cannot be 
	 * 	instantiated
	 *
	 * @author Cory Gehr
	 * @access private
	 */
	private function __construct() {}

	/**
	 * errorHandler()
	 * Error Handler for THINKer
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param int $errNo Error Level
	 * @param string $errMsg Error String
	 * @param string $errFile Error File
	 * @param int $errLine Error Line
	 * @param mixed[] $errContext Error Context Array
	 */
	public static function errorHandler($errNo, $errMsg, $errFile, $errLine, $errContext)
	{
		if ($errNo == E_USER_ERROR || $errNo == E_ERROR || $errNo == E_CORE_ERROR)
		{
			// Stop the execution of the page
			// Normally, errors are displayed at a specific point, but since the program is
			// stopping, the errors should be displayed right now.
			
			echo "
			A fatal error has occurred in this application. If you continue to see this message, 
			please contact the server administrator. Additional details are below:
			";
			
			echo "<br/>\n";
			
			echo "<pre>";
			echo "Error $errNo: $errMsg on Line $errLine in $errFile.";
			echo "</pre>";
			
			die();
		}
		else
		{
			// Compile message and push to message cache
			$message = "An error has occurred at Line $errLine in $errFile. Details: <i>$errMsg</i>.";
			Notification::push($message, 'error');
		}
	}
}
?>