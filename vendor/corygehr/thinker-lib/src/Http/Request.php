<?php
	/**
	 * Request.php
	 * Contains the Thinker\Http\Request class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\Http;

class Request {

	/**
	 * __construct()
	 * Constructor for the Thinker\Http\Request class
	 * This is intentionally private, so that it cannot be 
	 * 	instantiated
	 *
	 * @author Cory Gehr
	 * @access private
	 */
	private function __construct() {}

	/**
	 * cookie()
	 * Gets a variable submitted via cookie
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $name Variable Name
	 * @param boolean $required Flag to tell if variable is required (default: false)
	 * @return string Variable Value or NULL if failed validation
	 */
	public static function cookie($name, $required = false)
	{
		$val = (isset($_COOKIE[$name]) == true ? $_COOKIE[$name] : null);

		if(!isset($val) && $required)
		{
			trigger_error("Required cookie '$name' was not specified");
		}

		return $val;
	}

	/**
	 * get()
	 * Gets a variable submitted via the 'GET' method
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $name Variable Name
	 * @param boolean $required Flag to tell if variable is required (default: false)
	 * @param boolean $allowHtml Flag for allowing HTML (default: false)
	 * @return string Variable Value or NULL if failed validation
	 */
	public static function get($name, $required = false, $allowHtml = false)
	{
		$val = (isset($_GET[$name]) == true ? $_GET[$name] : null);
		
		if(isset($val))
		{
			// Sanitize
			$val = trim($val);

			if(!$allowHtml)
			{
				$val = htmlspecialchars(stripslashes($val));
			}
		}
		elseif($required)
		{
			// Variable not found
			trigger_error("Required GET parameter '$name' was not specified");
		}

		return $val;
	}

	/**
	 * post()
	 * Gets a variable submitted via the 'POST' method
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $name Variable Name
	 * @param boolean $required Flag to tell if variable is required (default: false)
	 * @param boolean $allowHtml Flag for allowing HTML (default: false)
	 * @return string Variable Value or NULL if failed validation
	 */
	public static function post($name, $required = false, $allowHtml = false)
	{
		$val = (isset($_POST[$name]) == true ? $_POST[$name] : null);

		if(isset($val))
		{
			// Sanitize
			$val = trim($val);

			if(!$allowHtml)
			{
				$val = htmlspecialchars(stripslashes($val));
			}
		}
		elseif($required)
		{
			// Variable not found
			trigger_error("Required POST parameter '$name' was not specified");
		}

		return $val;
	}

	/**
	 * request()
	 * Gets a variable from any method
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $name Variable Name
	 * @param boolean $required Flag to tell if variable is required (default: false)
	 * @param boolean $allowHtml Flag for allowing HTML (default: false)
	 * @return string Variable Value or NULL if failed validation
	 */
	public static function request($name, $required = false, $allowHtml = false)
	{
		$val = (isset($_REQUEST[$name]) == true ? $_REQUEST[$name] : null);

		if(isset($val))
		{
			// Sanitize
			$val = trim($val);

			if(!$allowHtml)
			{
				$val = htmlspecialchars(stripslashes($val));
			}
		}
		elseif($required)
		{
			// Variable not found
			trigger_error("Required parameter '$name' was not specified");
		}

		return $val;
	}
}
?>