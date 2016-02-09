<?php
	/**
	 * Persist.php
	 * Contains the Thinker\DataSource\Interface\PersistInterface interface
	 *
	 * @author Cory Gehr
	 */

namespace Thinker\DataSource\Interface;

interface PersistInterface {

	/**
	 * create()
	 * Adds the data to the specified data source
	 *
	 * @access public
	 * @param mixed[] $data: Data to be committed
	 * @return boolean True on Success, False on Failure
	 */
	public function create($data);

	/**
	 * read()
	 * Retrieves data based on its identifier
	 *
	 * @access public
	 * @param mixed $ids: Data identifiers
	 * @return mixed Data matching identifier
	 */
	public function read($ids);

	/**
	 * update()
	 * Updates data based on its identifier
	 *
	 * @access public
	 * @param mixed[] $ids Data identifiers
	 * @param mixed $data Data to be committed
	 * @return boolean True on Success, False on Failure
	 */
	public function update($ids, $data);

	/**
	 * delete()
	 * Deletes data based on its identifier
	 *
	 * @access public
	 * @param mixed $ids: Data identifiers
	 * @return boolean True on Success, False on Failure
	 */
	public function delete($ids);
}
?>