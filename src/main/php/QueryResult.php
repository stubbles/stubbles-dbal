<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db;
/**
 * Interface for database query results.
 */
interface QueryResult
{
    /**
     * bind a result column to a variable
     *
     * @param   int|string  $column     column number or name to bind the variable to
     * @param   mixed       &$variable  the variable to bind to the column
     * @param   int|string  $type       optional  type of the variable to bind
     * @return  bool        true on success, false on failure
     * @throws  DatabaseException
     */
    public function bindColumn($column, &$variable, $type = null);

    /**
     * fetch a result
     *
     * @param   int    $fetchMode      optional  the mode to use for fetching the data
     * @param   array  $driverOptions  optional  driver specific arguments
     * @return  mixed
     * @throws  DatabaseException
     */
    public function fetch($fetchMode = null, array $driverOptions = []);

    /**
     * fetch single column from the next row from a result set
     *
     * @param   int     $columnNumber  optional  the column number to fetch, default is first column
     * @return  string
     * @throws  DatabaseException
     */
    public function fetchOne($columnNumber = 0);

    /**
     * returns an array containing all of the result set rows
     *
     * @param   int    $fetchMode      optional  the mode to use for fetching the data
     * @param   array  $driverOptions  optional  driver specific arguments
     * @return  array
     * @throws  DatabaseException
     */
    public function fetchAll($fetchMode = null, array $driverOptions = []);

    /**
     * moves the internal result pointer to the next result row
     *
     * @return  bool  true on success, false on failure
     * @throws  DatabaseException
     */
    public function next();

    /**
     * returns the number of rows affected by the last SQL statement
     *
     * @return  int
     * @throws  DatabaseException
     */
    public function count();

    /**
     * releases resources allocated of the result set
     *
     * @return  bool  true on success, false on failure
     * @throws  DatabaseException
     */
    public function free();
}
