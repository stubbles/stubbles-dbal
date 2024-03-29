<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    public function __construct(private DatabaseConnection $dbConnection) { }

    /**
     * returns dsn of database connection
     *
     * @since  4.0.0
     */
    public function dsn(): string
    {
        return $this->dbConnection->dsn();
    }

    /**
     * sends a query to the database and returns amount of affected records
     *
     * @param  array<int|string,mixed>  $values
     * @since  3.1.0
     */
    public function query(string $sql, array $values = []): int
    {
        return $this->dbConnection->prepare($sql)
            ->execute($values)
            ->count();
    }

    /**
     * fetch single value from a result set
     *
     * @param   string                   $sql           sql query to fetch data with
     * @param   array<int|string,mixed>  $values        map of values in case $sql contains a prepared statement
     * @param   int                      $columnNumber  the column number to fetch, default is first column
     * @throws  DatabaseException
     * @since   3.1.0
     */
    public function fetchOne(string $sql, array $values = [], int $columnNumber = 0): string|false
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
     * @param   string                   $sql            sql query to fetch data with
     * @param   array<int|string,mixed>  $values         map of values in case $sql contains a prepared statement
     * @param   int                      $fetchMode      the mode to use for fetching the data
     * @param   array<string,mixed>      $driverOptions  driver specific arguments
     * @return  Sequence<mixed>
     */
    public function fetchAll(
        string $sql,
        array $values = [],
        int $fetchMode = null,
        array $driverOptions = []
    ): Sequence {
        return Sequence::of(new QueryResultIterator(
            $this->dbConnection->prepare($sql)->execute($values),
            $fetchMode,
            $driverOptions
        ));
    }

    /**
     * returns first result row for given sql query
     *
     * @param   string                   $sql            sql query to fetch data with
     * @param   array<int|string,mixed>  $values         map of values in case $sql contains a prepared statement
     * @param   int                      $fetchMode      the mode to use for fetching the data
     * @param   array<string,mixed>      $driverOptions  driver specific arguments
     * @return  mixed
     * @since   2.4.0
     */
    public function fetchRow(
        string $sql,
        array $values = [],
        int $fetchMode = null,
        array $driverOptions = []
    ): mixed {
        $result = $this->dbConnection->prepare($sql)
            ->execute($values)
            ->fetch($fetchMode, $driverOptions);
        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * return a list with all rows from given result
     *
     * Allows to map the result into a list of values from one single column:
     * <code>
     * $userNames = $database->fetchColumn('SELECT username FROM users WHERE status = "blocked"');
     * </code>
     *
     * @param   string                   $sql          sql query to fetch data with
     * @param   array<int|string,mixed>  $values       map of values in case $sql contains a prepared statement
     * @param   int                      $columnIndex  number of column to fetch
     * @return  Sequence<mixed>
     */
    public function fetchColumn(
        string $sql,
        array $values = [],
        int $columnIndex = 0
    ): Sequence {
        return $this->fetchAll(
            $sql,
            $values,
            \PDO::FETCH_COLUMN,
            ['columnIndex' => $columnIndex]
        );
    }
}
