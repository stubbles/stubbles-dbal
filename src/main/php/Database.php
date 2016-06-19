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
use stubbles\sequence\Sequence;
/**
 * Convenience access to database data to prevent fiddling with query results.
 *
 * @since  2.1.0
 * @ProvidedBy(stubbles\db\Databases.class)
 */
class Database
{
    /**
     * actual connection to be used
     *
     * @type  \stubbles\db\DatabaseConnection
     */
    private $dbConnection;

    /**
     * constructor
     *
     * @param  \stubbles\db\DatabaseConnection  $dbConnection
     */
    public function __construct(DatabaseConnection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * returns dsn of database connection
     *
     * @return  string
     * @since   4.0.0
     */
    public function dsn()
    {
        return $this->dbConnection->dsn();
    }

    /**
     * sends a query to the database and returns amount of affected records
     *
     * @param   string  $sql
     * @param   array   $values
     * @return  int
     * @since   3.1.0
     */
    public function query($sql, array $values = [])
    {
        return $this->dbConnection->prepare($sql)
                ->execute($values)
                ->count();
    }

    /**
     * fetch single value from a result set
     *
     * @param   string  $sql            sql query to fetch data with
     * @param   array   $values         map of values in case $sql contains a prepared statement
     * @param   int     $columnNumber  optional  the column number to fetch, default is first column
     * @return  string
     * @throws  \stubbles\db\DatabaseException
     * @since   3.1.0
     */
    public function fetchOne($sql, array $values = [], $columnNumber = 0)
    {
        return $this->dbConnection->prepare($sql)
                ->execute($values)
                ->fetchOne($columnNumber);
    }

    /**
     * return all result rows for given sql query
     *
     * Allows to fetch all results for a query at once.
     * <code>
     * $blockedUsers = $database->fetchAll('SELECT * FROM users WHERE status = "blocked"');
     * </code>
     * Now $blockedUsers contains all rows from the query.
     *
     * @param   string  $sql            sql query to fetch data with
     * @param   array   $values         map of values in case $sql contains a prepared statement
     * @param   int     $fetchMode      optional  the mode to use for fetching the data
     * @param   array   $driverOptions  optional  driver specific arguments
     * @return  \stubbles\sequence\Sequence
     */
    public function fetchAll($sql, array $values = [], $fetchMode = null, array $driverOptions = [])
    {
        return Sequence::of(
                new QueryResultIterator(
                        $this->dbConnection->prepare($sql)->execute($values),
                        $fetchMode,
                        $driverOptions
                )
        );
    }

    /**
     * returns first result row for given sql query
     *
     * @param   string  $sql            sql query to fetch data with
     * @param   array   $values         map of values in case $sql contains a prepared statement
     * @param   int     $fetchMode      optional  the mode to use for fetching the data
     * @param   array   $driverOptions  optional  driver specific arguments
     * @return  array
     * @since   2.4.0
     */
    public function fetchRow($sql, array $values = [], $fetchMode = null, array $driverOptions = [])
    {
        return $this->dbConnection->prepare($sql)
                ->execute($values)
                ->fetch($fetchMode, $driverOptions);
    }

    /**
     * return a list with all rows from given result
     *
     * Allows to map the result into a list of values from one single column:
     * <code>
     * $userNames = $database->fetchColumn('SELECT username FROM users WHERE status = "blocked"');
     * </code>
     *
     * @param   string  $sql          sql query to fetch data with
     * @param   array   $values       map of values in case $sql contains a prepared statement
     * @param   int     $columnIndex  number of column to fetch
     * @return  \stubbles\sequence\Sequence
     */
    public function fetchColumn($sql, array $values = [], $columnIndex = 0)
    {
        return $this->fetchAll(
                $sql,
                $values,
                \PDO::FETCH_COLUMN,
                ['columnIndex' => $columnIndex]
        );
    }
}
