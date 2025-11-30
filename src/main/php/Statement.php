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
     * @param   int|string  $param     the order number of the parameter or its name
     * @param   mixed       $variable  the variable to bind to the parameter
     * @param   int         $type      type of the parameter
     * @param   int         $length    length of the data type
     * @return  bool        true on success, false on failure
     */
    public function bindParam(
        int|string $param,
        mixed &$variable,
        ?int $type = null,
        ?int $length = null
    ): bool;

    /**
     * bind a value to the parameter of a prepared query
     *
     * In opposite to bindParam() this will use the value as it is at the time
     * when this method is called.
     *
     * @param   int|string  $param  the order number of the parameter or its name
     * @param   mixed       $value  the value to bind
     * @param   int         $type   optional  type of the parameter
     * @return  bool        true on success, false on failure
     */
    public function bindValue(int|string $param, mixed $value, ?int $type = null): bool;

    /**
     * executes a prepared statement
     *
     * @param   array<int|string,mixed>  $values  specifies all necessary information for bindParam()
     *                                            the array elements must use keys corresponding to the
     *                                            number of the position or name of the parameter
     */
    public function execute(array $values = []): QueryResult;

    /**
     * releases resources allocated for the specified prepared query
     *
     * Frees up the connection to the server so that other SQL statements may
     * be issued, but leaves the statement in a state that enables it to be
     * executed again.
     *
     * @return  bool  true on success, false on failure
     */
    public function clean(): bool;
}
