<?php
	/**
	 * Controller.php 
	 * Contains the Thinker\Framework\Controller class
	 *
	 * @author Cory Gehr
	 */
	 
namespace Thinker\Framework;

use \ReflectionClass;

abstract class Controller
{
	protected $reflectionClass;         // Contains information about the class (ReflectionClass)
	protected $data;                    // Contains the data being passed back from the Section
	protected $allowOpenAccess = false; // Flag to always allow access, if necessary
	public $session;       		        // Contains session object
	public $view;     			        // Contains the view being loaded for the section
	
	/**
	 * __construct()
	 * Constructor for the Thinker\Framework\Controller Class
	 *
	 * @author Cory Gehr
	 * @access public
	 */
	public function __construct()
	{
		// Initialize classInfo with information about the target class
		$this->reflectionClass = new ReflectionClass($this);
		$this->data = array();

		// Override view if necessary
		if(isset($_GET['view']))
		{
			// ucwords() allows users to use views as lowercase in URLs
			$this->view = ucwords(\Thinker\Http\Request::get('view', true));
		}
		else
		{
			$this->view = DEFAULT_VIEW;
		}

		// Get the session information
		if(SESSION_CLASS)
		{
			$sessionClass = SESSION_CLASS;
		}
		else
		{
			// No session specified, assume open site
			$sessionClass = 'Open';
		}
		
		$this->session = $sessionClass::singleton();

		if(!$this->allowOpenAccess && !$this->session->auth('section', array('section' => SECTION, 'subsection' => SUBSECTION)))
		{
			// Redirect to error
			\Thinker\Http\Redirect::error(403);
		}
	}

	/**
	 * defaultSubsection()
	 * Gets the default subsection for this section
	 *
	 * @access public
	 * @return string Default Subsection Name
	 */
	public static function defaultSubsection()
	{
		throw new \Exception("Must declare a default subsection!");
	}

	/**
	 * getData()
	 * Passes back the data array from this section
	 *
	 * @access public
	 * @return mixed[] Data Array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * getVal()
	 * Gets a value from this object
	 *
	 * @access public
	 * @param string $name Name of the Variable
	 * @return mixed Variable Value, or NULL on non-existence
	 */
	public function getVal($name)
	{
		if(isset($this->data[$name]))
		{
			return $this->data[$name];
		}
		else
		{
			return null;
		}
	}

	/**
	 * set()
	 * Sets a value in the local data array
	 *
	 * @access public
	 * @param $key: Array key
	 * @param $value: Value
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * toArray()
	 * Returns an object's variables as an associative array
	 * 
	 * @author Joe Stump <joe@joestump.net>
	 * @access public
	 * @return Array of Variables (VarName => Value)
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