<?php
	/**
	 * Redirect.php
	 * Contains the Thinker\Http\Redirect class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\Http;

class Redirect {

	/**
	 * __construct()
	 * Constructor for the Thinker\Http\Redirect class
	 * This is intentionally private, so that it cannot be 
	 * 	instantiated
	 *
	 * @author Cory Gehr
	 * @access private
	 */
	private function __construct() {}

	/**
	 * error()
	 * Redirects to an error page
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param int $no Error Number (default: 404)
	 */
	public static function error($no = 404)
	{
		// Call current redirect function
		Redirect::go('Error', 'info', array('no' => $no));
	}

	/**
	 * go()
	 * Redirects the user to the specified area
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $section Section Name
	 * @param string $subsection Subsection Name (default: null)
	 * @param string[] $params URL Parameters in an associative array (default: empty array)
	 */
	public static function go($section, $subsection = null, $params = array())
	{
		// Create URL
		$url = Url::create($section, $subsection, $params);

		// Replace &amp; with &
		$url = str_replace('&amp;', '&', $url);

		// Perform redirect
		header('Location: ' . $url);
		exit();
	}
}
?>