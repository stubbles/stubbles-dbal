<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\pdo;
use stubbles\db\DatabaseException;
use stubbles\db\QueryResult;
use stubbles\db\Statement;
use PDOException;
/**
 * Wrapper around the PDOStatement object.
 *
 * @see  http://php.net/pdo
 */
class PdoStatement implements Statement
{
    /**
     * the wrapped pdo statement
     *
     * @var  \PDOStatement<mixed>
     */
    protected $pdoStatement;

    /**
     * constructor
     *
     * @param  \PDOStatement<mixed>  $pdoStatement  the pdo statement to wrap
     */
    public function __construct(\PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

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
     * @param   int         $type      optional  type of the parameter
     * @param   int         $length    optional  length of the data type
     * @return  bool        true on success, false on failure
     * @throws  \stubbles\db\DatabaseException
     * @see     http://php.net/pdostatement-bindParam
     */
    public function bindParam($param, &$variable, int $type = null, int $length = null): bool
    {
        if (null === $type) {
            $type = \PDO::PARAM_STR;
        }

        try {
            if (null === $length) {
                return $this->pdoStatement->bindParam($param, $variable, $type);
            }

            return $this->pdoStatement->bindParam($param, $variable, $type, $length, null);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

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
     * @throws  \stubbles\db\DatabaseException
     * @see     http://php.net/pdostatement-bindValue
     */
    public function bindValue($param, $value, int $type = null): bool
    {
        if (null === $type) {
            $type = \PDO::PARAM_STR;
        }

        try {
            return $this->pdoStatement->bindValue($param, $value, $type);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * executes a prepared statement
     *
     * @param   array<int|string,mixed>  $values  optional  specifies all necessary information for bindParam()
     *                                            the array elements must use keys corresponding to the
     *                                            number of the position or name of the parameter
     * @return  \stubbles\db\pdo\PdoQueryResult
     * @throws  \stubbles\db\DatabaseException
     * @see     http://php.net/pdostatement-execute
     */
    public function execute(array $values = []): QueryResult
    {
        try {
            if ($this->pdoStatement->execute($values)) {
                return new PdoQueryResult($this->pdoStatement);
            }

            throw new DatabaseException('Executing the prepared statement failed.');
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * releases resources allocated for the specified prepared query
     *
     * Frees up the connection to the server so that other SQL statements may
     * be issued, but leaves the statement in a state that enables it to be
     * executed again.
     *
     * @return  bool  true on success, false on failure
     */
    public function clean(): bool
    {
        try {
            return $this->pdoStatement->closeCursor();
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }
}
