<?php
	/**
	 * Factory.php
	 * Contains the Thinker\DataSource\Factory class
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\DataSource;

class Factory implements Thinker\DataSource\Interface\FactoryInterface {

	/**
	 * build()
	 * Creates an object
	 *
	 * @access public
	 * @param mixed[] $data Data used to create the object
	 * @return Object Object of the defined type
	 */
	public function build($data);
}
?>