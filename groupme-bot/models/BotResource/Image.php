<?php
	/**
	 * models/BotResource/Image.php 
	 * Contains the Image class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

class Image extends Attachment
{
	public $url;

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
		return array(
			"type" => $this->get_type(),
			"url" => $this->url
		);
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
		return "image";
	}
}