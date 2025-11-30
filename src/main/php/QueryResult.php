<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @param   int|string  $column    column number or name to bind the variable to
     * @param   mixed       $variable  the variable to bind to the column
     * @param   int         $type      type of the variable to bind
     * @return  bool        true on success, false on failure
     */
    public function bindColumn(int|string $column, &$variable, ?int $type = null): bool;

    /**
     * fetch a result
     *
     * @param  int                  $fetchMode      the mode to use for fetching the data
     * @param  array<string,mixed>  $driverOptions  driver specific arguments
     */
    public function fetch(?int $fetchMode = null, array $driverOptions = []): mixed;

    /**
     * fetch single column from the next row from a result set
     *
     * @param  int  $columnNumber  the column number to fetch, default is first column
     */
    public function fetchOne(int $columnNumber = 0): mixed;

    /**
     * returns an array containing all of the result set rows
     *
     * @param   int                  $fetchMode      the mode to use for fetching the data
     * @param   array<string,mixed>  $driverOptions  driver specific arguments
     * @return  array<string,mixed>
     */
    public function fetchAll(?int $fetchMode = null, array $driverOptions = []): array;

    /**
     * moves the internal result pointer to the next result row
     *
     * @return  bool  true on success, false on failure
     */
    public function next(): bool;

    /**
     * returns the number of rows affected by the last SQL statement
     */
    public function count(): int;

    /**
     * releases resources allocated of the result set
     *
     * @return  bool  true on success, false on failure
     */
    public function free(): bool;
}
