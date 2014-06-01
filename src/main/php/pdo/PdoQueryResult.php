<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\pdo;
use stubbles\db\DatabaseException;
use stubbles\db\QueryResult;
use stubbles\lang\exception\IllegalArgumentException;
use PDO;
use PDOException;
/**
 * Wrapper around the pdo connection.
 *
 * @see  http://php.net/pdo
 */
class PdoQueryResult implements QueryResult
{
    /**
     * result set
     *
     * @type  \PDOStatement
     */
    private $pdoStatement;

    /**
     * constructor
     *
     * @param  \PDOStatement  $pdoStatement
     */
    public function __construct(\PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

    /**
     * bind a result column to a variable
     *
     * @param   int|string  $column     column number or name to bind the variable to
     * @param   mixed       &$variable  the variable to bind to the column
     * @param   int|string  $type       optional  type of the binded variable
     * @return  bool        true on success, false on failure
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-bindColumn
     */
    public function bindColumn($column, &$variable, $type = null)
    {
        try {
            return $this->pdoStatement->bindColumn($column, $variable, $type, null, null);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * fetch a result
     *
     * @param   int    $fetchMode      optional  the mode to use for fetching the data
     * @param   array  $driverOptions  optional  driver specific arguments
     * @return  mixed
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-fetch
     */
    public function fetch($fetchMode = null, array $driverOptions = [])
    {
        if (null === $fetchMode) {
            $fetchMode = PDO::FETCH_BOTH;
        }

        try {
            return $this->pdoStatement->fetch($fetchMode,
                                              ((!isset($driverOptions['cursorOrientation'])) ? (null) : ($driverOptions['cursorOrientation'])),
                                              ((!isset($driverOptions['cursorOffset'])) ? (null) : ($driverOptions['cursorOffset']))
                   );
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * fetch single column from the next row from a result set
     *
     * @param   int     $columnNumber  optional  the column number to fetch, default is first column
     * @return  string
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-fetchColumn
     */
    public function fetchOne($columnNumber = 0)
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
     * @param   int    $fetchMode      optional  the mode to use for fetching the data
     * @param   array  $driverOptions  optional  driver specific arguments
     * @return  array
     * @throws  DatabaseException
     * @throws  IllegalArgumentException
     * @see     http://php.net/pdostatement-fetchAll
     */
    public function fetchAll($fetchMode = null, array $driverOptions = [])
    {
        try {
            if (null === $fetchMode) {
                return $this->pdoStatement->fetchAll();
            }

            if (PDO::FETCH_COLUMN == $fetchMode) {
                return $this->pdoStatement->fetchAll(PDO::FETCH_COLUMN,
                                                     (!(isset($driverOptions['columnIndex'])) ? (0) : ($driverOptions['columnIndex']))
                       );
            }

            if (PDO::FETCH_CLASS == $fetchMode) {
                if (!isset($driverOptions['classname'])) {
                    throw new IllegalArgumentException('Tried to use PDO::FETCH_CLASS but no classname given in driver options.');
                }

                return $this->pdoStatement->fetchAll(PDO::FETCH_CLASS,
                                                     $driverOptions['classname'],
                                                     (!(isset($driverOptions['arguments'])) ? (null) : ($driverOptions['arguments']))
                       );
            }

            if (PDO::FETCH_FUNC == $fetchMode) {
                if (!isset($driverOptions['function'])) {
                    throw new IllegalArgumentException('Tried to use PDO::FETCH_FUNC but no function given in driver options.');
                }

                return $this->pdoStatement->fetchAll(PDO::FETCH_FUNC,
                                                     $driverOptions['function']
                       );
            }

            return $this->pdoStatement->fetchAll($fetchMode);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * moves the internal result pointer to the next result row
     *
     * @return  bool  true on success, false on failure
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-nextRowset
     */
    public function next()
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
     * @return  int
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-rowCount
     */
    public function count()
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
     * @return  bool  true on success, false on failure
     * @throws  DatabaseException
     * @see     http://php.net/pdostatement-closeCursor
     */
    public function free()
    {
        try {
            return $this->pdoStatement->closeCursor();
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }
}
