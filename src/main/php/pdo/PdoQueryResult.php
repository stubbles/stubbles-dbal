<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\pdo;

use InvalidArgumentException;
use Override;
use stubbles\db\DatabaseException;
use stubbles\db\QueryResult;
use PDO;
use PDOException;
use PDOStatement as PhpPdoStatement;
/**
 * Wrapper around the pdo connection.
 *
 * @see  http://php.net/pdo
 */
class PdoQueryResult implements QueryResult
{
    public function __construct(private PhpPdoStatement $pdoStatement) { }

    /**
     * bind a result column to a variable
     *
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-bindColumn
     */
    #[Override]
    public function bindColumn(int|string $column, &$variable, int $type = null): bool
    {
        try {
            if (null === $type) {
              return $this->pdoStatement->bindColumn($column, $variable);
            }

            return $this->pdoStatement->bindColumn($column, $variable, $type);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * fetch a result
     *
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-fetch
     */
    #[Override]
    public function fetch(int $fetchMode = null, array $driverOptions = []): mixed
    {
        if (null === $fetchMode) {
            $fetchMode = PDO::FETCH_ASSOC;
        }

        try {
            return $this->pdoStatement->fetch(
                    $fetchMode,
                    $driverOptions['cursorOrientation'] ?? PDO::FETCH_ORI_NEXT,
                    $driverOptions['cursorOffset'] ?? 0
            );
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * fetch single column from the next row from a result set
     *
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-fetchColumn
     */
    #[Override]
    public function fetchOne(int $columnNumber = 0): mixed
    {
        try {
            return $this->pdoStatement->fetchColumn($columnNumber);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * returns an array containing all of the result set rows
     *
     * @throws  DatabaseException
     * @throws  InvalidArgumentException
     * @see     http://php.net/pdostatement-fetchAll
     */
    #[Override]
    public function fetchAll(int $fetchMode = null, array $driverOptions = []): array
    {
        try {
            if (null === $fetchMode) {
                return $this->wrapResult($this->pdoStatement->fetchAll());
            }

            if (PDO::FETCH_COLUMN == $fetchMode) {
                return $this->wrapResult($this->pdoStatement->fetchAll(
                        PDO::FETCH_COLUMN,
                        $driverOptions['columnIndex'] ?? 0
                ));
            }

            if (PDO::FETCH_CLASS == $fetchMode) {
                if (!isset($driverOptions['classname'])) {
                    throw new InvalidArgumentException(
                        'Tried to use PDO::FETCH_CLASS but no classname given in driver options.'
                    );
                }

                return $this->wrapResult($this->pdoStatement->fetchAll(
                        PDO::FETCH_CLASS,
                        $driverOptions['classname'],
                        $driverOptions['arguments'] ?? null
                ));
            }

            if (PDO::FETCH_FUNC == $fetchMode) {
                if (!isset($driverOptions['function'])) {
                    throw new InvalidArgumentException(
                        'Tried to use PDO::FETCH_FUNC but no function given in driver options.'
                    );
                }

                return $this->wrapResult($this->pdoStatement->fetchAll(
                        PDO::FETCH_FUNC,
                        $driverOptions['function']
                ));
            }

            return $this->wrapResult($this->pdoStatement->fetchAll($fetchMode));
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * @param   array<string,mixed>|false  $result
     * @return  array<string,mixed>
     */
    private function wrapResult(array|false $result): array
    {
        if (false === $result) {
            throw new DatabaseException('An unknown error occurred.');
        }

        return $result;
    }

    /**
     * moves the internal result pointer to the next result row
     *
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-nextRowset
     */
    public function next(): bool
    {
        try {
            return $this->pdoStatement->nextRowset();
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * returns the number of rows affected by the last SQL statement
     *
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-rowCount
     */
    public function count(): int
    {
        try {
            return $this->pdoStatement->rowCount();
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * releases resources allocated of the result set
     *
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-closeCursor
     */
    public function free(): bool
    {
        try {
            return $this->pdoStatement->closeCursor();
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }
}
