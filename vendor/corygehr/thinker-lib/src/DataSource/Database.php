<?php
	/**
	 * Database.php
	 * Contains the Thinker\DataSource\Database class (extends the PHP PDO class)
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\DataSource;

class Database extends \PDO
{
	// Private Class Variables
	private $driver; 
	private $host; 
	private $port;
	private $schema; 
	private $user; 
	private $pass;
	private $auto_connect;

	/**
	 * __construct()
	 * Constructor for the Thinker\DataSource\Database Class
	 *
	 * @access public
	 * @param string[] $settings Connection Settings
	 */
	public function __construct($settings)
	{ 
		// Get desired configuration options
		$this->user = $settings['user'];
		$this->pass = $settings['password'];
		$this->driver = $settings['driver'];

		// DSN Properties
		$this->host = $settings['host'];
		$this->port = $settings['port'];
		$this->schema = $settings['schema'];

		// Additional options
		$this->auto_connect = $settings['auto_connect'];

		// PDO Options
		//$options = $settings['pdo_options'];

		if($this->auto_connect)
		{
			$this->connect(true);
		}
	}

	/**
	 * connect()
	 * Opens a connection to the database
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param boolean $dieOnError Kills script execution on Exception (default: false)
	 * @return boolean True on Success, False on Failure
	 */
	public function connect($dieOnError = false)
	{
		// Data Source String
		$dsn = $this->driver . ":dbname=" . $this->schema . ";host=" . $this->host . ";port=" . $this->port;

		// Attempt to make connection
		try
		{
			// '@' suppresses errors from this function. We output our own message if there's a problem
			@parent::__construct($dsn, $this->user, $this->pass);
		}
		catch(\PDOException $err)
		{
			if($dieOnError)
			{
				die("Unable to connect to the database server. Details: " . $err->getMessage() . ".");
			}
			else
			{
				\Thinker\Framework\Notification::push("Failed to connect to database '" . $this->schema . "'. Details: " . $err->getMessage(), 'error');
			}

			return false;
		}

		return true;
	}

	/**
	 * doQuery()
	 * Executes a MySQL Query
	 * Based on Adam Zydney's function from THINK Again
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string $query MySQL Query String
	 * @param mixed[] $data Parameters for the query
	 * @return boolean True on Success, False on Failure
	 */
	public function doQuery($query, $data = null)
	{
		// Prepare the entered query
		$prepQuery = parent::prepare($query);
		// Execute query, return result
		return $prepQuery->execute($data);
	}
	
	/**
	 * doQueryAns()
	 * Returns a single value from the database
	 * Based on Adam Zydney's function from THINK Again
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string $query MySQL Query String
	 * @param mixed[] $data Parameters for the query
	 * @return mixed Single database value
	 */
	public function doQueryAns($query, $data = null)
	{
		// Prepare and execute the entered query
		$prepQuery = parent::prepare($query);
		$prepQuery->execute($data);
		
		// Return the first column, AKA the only column 
		return $prepQuery->fetchColumn(0);
	}
	
	/**
	 * doQueryArr()
	 * Returns an array of all returned objects
	 * Based on Adam Zydney's function from THINK Again
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string $query MySQL Query String
	 * @param mixed[] $data Parameters for the query
	 * @return mixed[] Array of database values
	 */
	public function doQueryArr($query, $data = null)
	{
		// Prepare and execute the entered query
		$prepQuery = parent::prepare($query);
		$prepQuery->execute($data);
		
		// Return the entire result set
		return $prepQuery->fetchAll();
		// Calling fetchAll like this means we get the numerical and column-keyed index
		// Oracle and MSSQL support giving only the key using PDO::FETCH_ASSOC, but MySQL does not. Could be useful though?
	}
	
	/**
	 * doQueryOne()
	 * Returns a single row of data
	 * Based on Adam Zydney's function from THINK Again
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string $query MySQL Query String
	 * @param mixed[] $data Parameters for the query
	 * @return mixed[] Database data row
	 */
	public function doQueryOne($query, $data = null)
	{
		// Prepare and execute the entered query
		$prepQuery = parent::prepare($query);
		$prepQuery->execute($data);
		
		// Since this function is only to return one row, only return the first row
		return $prepQuery->fetch();
	}

	/**
	 * fetchClass()
	 * Returns an object of the specified type, created from the database
	 *
	 * @author Cory Gehr
	 * @access public
	 * @param string $query MySQL Query String
	 * @param mixed[] $data Parameters for the query
	 * @param string $class Class Name
	 * @return mixed Created object
	 */
	public function fetchClass($query, $data, $class)
	{
		// Prepare the query
		$prepQuery = parent::prepare($query);
		// Change the fetch mode to FETCH_CLASS
		$prepQuery->setFetchMode(PDO::FETCH_CLASS, $class);
		// Now execute
		$prepQuery->execute($data);

		// Return the created class
		return $prepQuery->fetch();
	}
}
?>
