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
 * Interface for database connections.
 *
 * @ProvidedBy(stubbles\db\DatabaseConnections.class)
 */
interface DatabaseConnection
{
    /**
     * returns dsn of connection
     *
     * @since  2.1.0
     */
    public function dsn(): string;

    /**
     * returns details about the connection
     *
     * @since  2.1.0
     */
    public function details(): ?string;

    /**
     * returns property with given name or given default if property not set
     *
     * @since  2.2.0
     */
    public function property(string $name, mixed $default = null): mixed;

    /**
     * establishes the connection
     */
    public function connect(): self;

    /**
     * disconnects the database
     */
    public function disconnect(): void;

    /**
     * start a transaction
     */
    public function beginTransaction(): bool;

    /**
     * commit a transaction
     */
    public function commit(): bool;

    /**
     * rollback a transaction
     */
    public function rollback(): bool;

    /**
     * creates a prepared statement
     *
     * @param  array<string,mixed>   $driverOptions  one or more key=>value pairs to set attribute values
     */
    public function prepare(string $statement, array $driverOptions = []): Statement;

    /**
     * executes a SQL statement
     *
     * @param  array<string,mixed>   $driverOptions  one or more driver specific options for the call to query()
     */
    public function query(string $sql, array $driverOptions = []): QueryResult;

    /**
     * execute an SQL statement and return the number of affected rows
     */
    public function exec(string $statement): int;

    /**
     * returns the last insert id
     */
    public function getLastInsertId(string $name = null): string;
}
