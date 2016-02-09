<?php
	/**
	 * Model.php 
	 * Contains the Thinker\Framework\Model class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\Framework;

abstract class Model
{
	protected $reflectionClass;	// Contains information about the class (ReflectionClass)
	
	/**
	 * __construct()
	 * Constructor for the Thinker\Framework\Model Class
	 *
	 * @author Cory Gehr
	 * @access public
	 */
	public function __construct()
	{
		// Initialize reflectionClass with information about the target class
		$this->reflectionClass = new \ReflectionClass($this);
	}
	
	/**
	 * __get()
	 * Gets a value from this Model
	 *
	 * @access public
	 * @param string $varName Variable Name
	 * @return mixed Variable Value, or NULL if it doesn't exist
	 */
	public function __get($varName)
	{
		if(isset($this->$varName))
		{
			return $this->$varName;
		}
		else
		{
			return null;
		}
	}

	/**
	 * __set()
	 * Sets a value on this Model
	 *
	 * @access public
	 * @param string $varName Variable Name
	 * @param mixed $val: Value
	 * @return mixed Value being assigned
	 */
	public function __set($varName, $val)
	{
		$this->$varName = $val;
		return $val;
	}

	/**
	 * toArray()
	 * Returns an object's variables as an associative array
	 * 
	 * @author Joe Stump <joe@joestump.net>
	 * @access public
	 * @return mixed[] Array of Variables (VarName => Value)
	 */
	public function toArray()
	{
		$defaults = $this->reflectionClass->getDefaultProperties();
		$return = array();
		
		foreach($defaults as $var => $val)
		{
			if($this->$var instanceof Model)
			{
				$return[$var] = $this->$var->toArray();
			}
			else
			{
				$return[$var] = $this->$var;
			}
		}
		
		return $return;
	}
}