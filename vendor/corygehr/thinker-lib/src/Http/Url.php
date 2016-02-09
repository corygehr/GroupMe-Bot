<?php
	/**
	 * Url.php
	 * Contains the Thinker\Http\Url class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\Http;

class Url {

	/**
	 * __construct()
	 * Constructor for the Thinker\Http\Url class
	 * This is intentionally private, so that it cannot be 
	 * 	instantiated
	 *
	 * @author Cory Gehr
	 * @access private
	 */
	private function __construct() {}

	/**
	 * create()
	 * Creates an internal site URL
	 *
	 * @author Cory Gehr
	 * @access public
	 * @static
	 * @param string $section Section Name
	 * @param string $subsection Subsection Name (default: null)
	 * @param string[] $params URL Parameters (default: empty array)
	 * @return string Full URL to Item
	 */
	public static function create($section, $subsection = null, $params = array())
	{
		$url =	BASE_URL . 'index.php?s=' . urlencode($section);

		if($subsection)
		{
			$url .= "&amp;su=" . urlencode($subsection);
		}
		
		// Add URL Parameters
		if(count($params) > 0)
		{
			foreach($params as $key => $value)
			{
				$url .= ("&amp;$key=" . urlencode($value));
			}
		}
		
		return $url;
	}
}
?>