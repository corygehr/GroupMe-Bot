<?php
	/**
	 * models/BotResource/Location.php 
	 * Contains the Location class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class Location extends Attachment
{
	public $lng;
	public $lat;
	public $name;

	/**
	 * compile_attributes()
	 * Returns all attributes in an array for processing
	 *
	 * @author Cory Gehr
	 * @access public
	 * @return Array of attributes
	 */
	public function compile_attributes()
	{
		$result = array(
			"type" => $this->get_type()
		);
		
		foreach(get_class_vars() as $k => $v)
		{
			$result[$k] = $this->{$k};
		}

		return $result;
	}

	/**
	 * get_type()
	 * Returns the type of the attachment
	 *
	 * @access public
	 * @return Type Name
	 */
	public function get_type()
	{
		return "location";
	}
}