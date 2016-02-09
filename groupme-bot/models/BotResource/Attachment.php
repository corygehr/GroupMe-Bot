<?php
	/**
	 * models/BotResource/Attachment.php 
	 * Contains the Attachment class
	 *
	 * @author Cory Gehr
	 */

namespace GroupMeBot\BotResource;

abstract class Attachment extends \Thinker\Framework\Model
{
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
	abstract public function get_type();
}