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
 * Interface for database connections.
 *
 * @ProvidedBy(net\stubbles\db\ioc\ConnectionProvider.class)
 */
interface DatabaseConnection
{
    /**
     * returns dsn of connection
     *
     * @return  string
     * @since   2.1.0
     */
    public function dsn();

    /**
     * returns details about the connection
     *
     * @return  string
     * @since   2.1.0
     */
    public function details();

    /**
     * establishes the connection
     *
     * @return  DatabaseConnection
     * @throws  DatabaseException
     */
    public function connect();

    /**
     * disconnects the database
     */
    public function disconnect();

    /**
     * start a transaction
     *
     * @return  bool
     * @throws  DatabaseException
     */
    public function beginTransaction();

    /**
     * commit a transaction
     *
     * @return  bool
     * @throws  DatabaseException
     */
    public function commit();

    /**
     * rollback a transaction
     *
     * @return  bool
     * @throws  DatabaseException
     */
    public function rollback();

    /**
     * creates a prepared statement
     *
     * @param   string  $statement      sql statement
     * @param   array   $driverOptions  optional  one or more key=>value pairs to set attribute values for the Statement object
     * @return  Statement
     * @throws  DatabaseException
     */
    public function prepare($statement, array $driverOptions = array());

    /**
     * executes a SQL statement
     *
     * @param   string  $sql            sql query to use
     * @param   array   $driverOptions  optional  one or more driver specific options for the call to query()
     * @return  QueryResult
     * @throws  DatabaseException
     */
    public function query($sql, array $driverOptions = array());

    /**
     * execute an SQL statement and return the number of affected rows
     *
     * @param   string  $statement      the sql statement to execute
     * @return  int     number of effected rows
     * @throws  DatabaseException
     */
    public function exec($statement);

    /**
     * returns the last insert id
     *
     * @param   string  $name  optional  identifier to where to retrieve the last insert id from
     * @return  int
     * @throws  DatabaseException
     */
    public function getLastInsertId($name = null);
}
