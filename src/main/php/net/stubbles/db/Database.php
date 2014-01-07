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
 * Convenience access to database data to prevent fiddling with query results.
 *
 * @since  2.1.0
 * @ProvidedBy(net\stubbles\db\ioc\DatabaseProvider.class)
 */
class Database
{
    /**
     * actual connection to be used
     *
     * @type  DatabaseConnection
     */
    private $dbConnection;

    /**
     * constructor
     *
     * @param  DatabaseConnection  $dbConnection
     */
    public function __construct(DatabaseConnection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * return all rows from given result
     *
     * Allows to fetch all results for a query at once.
     * <code>
     * $blockedUsers = $database->fetchAll('SELECT * FROM users WHERE status = "blocked"');
     * </code>
     * Now $blockedUsers contains all rows from the query.
     *
     * @param   string  $sql            sql query to fetch data with
     * @param   int     $fetchMode      optional  the mode to use for fetching the data
     * @param   array   $driverOptions  optional  driver specific arguments
     * @return  array
     */
    public function fetchAll($sql, $fetchMode = null, array $driverOptions = [])
    {
        return $this->dbConnection->query($sql)->fetchAll($fetchMode, $driverOptions);
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
     * @param   int     $columnIndex  number of column to fetch
     * @return  string[]
     */
    public function fetchColumn($sql, $columnIndex = 0)
    {
        return $this->fetchAll($sql, \PDO::FETCH_COLUMN, ['columnIndex' => $columnIndex]);
    }

    /**
     * map all result rows using given function
     *
     * Allows to apply a mapping to each row of the query result:
     * <code>
     * $users = $database->map('SELECT * FROM users WHERE status = "blocked"',
     *                         function($userRow)
     *                         {
     *                             return User::fromArray($userRow);
     *                         }
     *          );
     * </code>
     * In this case the value of $users would be a list of User instances
     * instead of just the single record rows from the database.
     *
     * @param  string    $sql       sql query to map results of
     * @param  \Closure  $function  function to apply to each result row
     */
    public function map($sql, \Closure $function)
    {
        $result      = [];
        $queryResult = $this->dbConnection->query($sql);
        while ($row = $queryResult->fetch()) {
            $result[] = $function($row);
        }

        return $result;
    }
}
