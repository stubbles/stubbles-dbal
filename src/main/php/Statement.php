<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db;
/**
 * Interface for database statements.
 */
interface Statement
{
    /**
     * bind a parameter of a prepared query to the specified variable
     *
     * The binding will be via reference, so it is evaluated at the time when
     * the prepared statement is executed meaning that in opposite to
     * bindValue() the value of the variable at the time of execution will be
     * used, not the value at the time when this method is called.
     *
     * @param   int|string  $param      the order number of the parameter or its name
     * @param   mixed       &$variable  the variable to bind to the parameter
     * @param   int|string  $type       optional  type of the parameter
     * @param   int         $length     optional  length of the data type
     * @return  bool        true on success, false on failure
     * @throws  DatabaseException
     */
    public function bindParam($param, &$variable, $type = null, $length = null);

    /**
     * bind a value to the parameter of a prepared query
     *
     * In opposite to bindParam() this will use the value as it is at the time
     * when this method is called.
     *
     * @param   int|string  $param  the order number of the parameter or its name
     * @param   mixed       $value  the value to bind
     * @param   int|string  $type   optional  type of the parameter
     * @return  bool        true on success, false on failure
     * @throws  DatabaseException
     */
    public function bindValue($param, $value, $type = null);

    /**
     * executes a prepared statement
     *
     * @param   array  $values  optional  specifies all necessary information for bindParam()
     *                                    the array elements must use keys corresponding to the
     *                                    number of the position or name of the parameter
     * @return  QueryResult
     * @throws  DatabaseException
     */
    public function execute(array $values = []);

    /**
     * releases resources allocated for the specified prepared query
     *
     * Frees up the connection to the server so that other SQL statements may
     * be issued, but leaves the statement in a state that enables it to be
     * executed again.
     *
     * @return  bool  true on success, false on failure
     * @throws  DatabaseException
     */
    public function clean();
}
