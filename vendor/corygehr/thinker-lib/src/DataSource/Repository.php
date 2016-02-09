<?php
	/**
	 * Repository.php
	 * Contains the Thinker\DataSource\Repository class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\DataSource;

abstract class Repository {
	
	// The object that tells us how to manipulate the object
	protected $persistence = null;

	/**
	 * __construct()
	 * Constructor for the Repository object
	 *
	 * @access public
	 * @param PersistInterface Persistence object
	 * @return Repository Repository Object
	 */
	public function __construct(PersistInterface $persistence = null);
}
?>