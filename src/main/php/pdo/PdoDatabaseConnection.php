<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\pdo;

use BadMethodCallException;
use InvalidArgumentException;
use stubbles\db\DatabaseConnection;
use stubbles\db\DatabaseException;
use stubbles\db\QueryResult;
use stubbles\db\Statement;
use stubbles\db\config\DatabaseConfiguration;
use PDO;
use PDOException;
use PDOStatement as PhpPdoStatement;

/**
 * Wrapper around the pdo connection.
 *
 * @see  http://php.net/pdo
 */
class PdoDatabaseConnection implements DatabaseConnection
{
    /**
     * closure to create a pdo instance
     *
     * @var  callable|null
     */
    private $pdoCreator;
    private ?PDO $pdo = null;

    public function __construct(
        private DatabaseConfiguration $configuration,
        ?callable $pdoCreator = null
    ) {
        $this->pdoCreator    = $pdoCreator;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * returns dsn of connection
     *
     * @since  2.1.0
     */
    public function dsn(): string
    {
        return $this->configuration->getDsn();
    }

    /**
     * returns details about the connection
     *
     * @since  2.1.0
     */
    public function details(): ?string
    {
        return $this->configuration->getDetails();
    }

    /**
     * returns property with given name or given default if property not set
     *
     * @since  2.2.0
     */
    public function property(string $name, mixed $default = null): mixed
    {
        return $this->configuration->getProperty($name, $default);
    }

    /**
     * establishes the connection
     *
     * @throws  DatabaseException
     */
    public function connect(): DatabaseConnection
    {
        if (null !== $this->pdo) {
            return $this;
        }

        try {
            $pdoCreator = $this->getPdoCreator();
            $this->pdo  = $pdoCreator($this->configuration);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            if ($this->configuration->hasInitialQuery()) {
                $this->pdo->query($this->configuration->getInitialQuery());
            }
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }

        return $this;
    }

    private function getPdoCreator(): callable
    {
        if (null !== $this->pdoCreator) {
            return $this->pdoCreator;
        }

        return function(DatabaseConfiguration $configuration): PDO
        {
            if (!$configuration->hasDriverOptions()) {
                return new PDO(
                    $configuration->getDsn(),
                    $configuration->getUserName(),
                    $configuration->getPassword()
                );
            }

            return new PDO(
                $configuration->getDsn(),
                $configuration->getUserName(),
                $configuration->getPassword(),
                $configuration->getDriverOptions()
            );
        };
    }

    public function disconnect(): void
    {
        unset($this->pdo);
    }

    /**
     * redirects calls on non-existing methods to the pdo object
     *
     * @param   mixed[]  $arguments  list of arguments for the method call
     * @throws  DatabaseException
     * @throws  BadMethodCallException
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        if (!method_exists($this->pdo, $method)) {
            throw new BadMethodCallException(
                'Call to undefined method ' . __CLASS__ . '::' . $method . '()'
            );
        }

        try {
            return $this->pdo->$method(...$arguments);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * start a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->__call('beginTransaction', []);
    }

    /**
     * commit a transaction
     */
    public function commit(): bool
    {
        return $this->__call('commit', []);
    }

    /**
     * rollback a transaction
     */
    public function rollback(): bool
    {
        return $this->__call('rollBack', []);
    }

    /**
     * @param   array<string,mixed>  $driverOptions
     * @throws  DatabaseException
     * @see     http://php.net/pdo-prepare
     */
    public function prepare(string $statement, array $driverOptions = []): Statement
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        try {
            return new PdoStatement(
                $this->pdo->prepare($statement, $driverOptions)
            );
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * executes a SQL statement
     *
     * The driver options can be:
     * <code>
     * fetchMode => one of the PDO::FETCH_* constants
     * colNo     => if fetchMode == PDO::FETCH_COLUMN this denotes the column number to fetch
     * object    => if fetchMode == PDO::FETCH_INTO this denotes the object to fetch the data into
     * classname => if fetchMode == PDO::FETCH_CLASS this denotes the class to create and fetch the data into
     * ctorargs  => (optional) if fetchMode == PDO::FETCH_CLASS this denotes the list of arguments
     *              for the constructor of the class to create and fetch the data into
     * </code>
     *
     * @param   array<string,mixed>  $driverOptions
     * @throws  DatabaseException
     * @throws  InvalidArgumentException
     * @see     http://php.net/pdo-query
     * @see     http://php.net/pdostatement-setfetchmode for the details on the fetch mode options
     */
    public function query(string $sql, array $driverOptions = []): QueryResult
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        try {
            if (!isset($driverOptions['fetchMode'])) {
                return new PdoQueryResult($this->handleFalse($this->pdo->query($sql)));
            }

            switch ($driverOptions['fetchMode']) {
                case PDO::FETCH_COLUMN:
                    if (!isset($driverOptions['colNo'])) {
                        throw new InvalidArgumentException(
                            'Fetch mode COLUMN requires driver option colNo.'
                        );
                    }

                    $pdoStatement = $this->pdo->query(
                        $sql,
                        $driverOptions['fetchMode'],
                        $driverOptions['colNo']
                    );
                    break;

                case PDO::FETCH_INTO:
                    if (!isset($driverOptions['object'])) {
                        throw new InvalidArgumentException(
                            'Fetch mode INTO requires driver option object.'
                        );
                    }

                    $pdoStatement = $this->pdo->query(
                        $sql,
                        $driverOptions['fetchMode'],
                        $driverOptions['object']
                    );
                    break;

                case PDO::FETCH_CLASS:
                    if (!isset($driverOptions['classname'])) {
                        throw new \InvalidArgumentException(
                            'Fetch mode CLASS requires driver option classname.'
                        );
                    }

                    $pdoStatement = $this->pdo->query(
                        $sql,
                        $driverOptions['fetchMode'],
                        $driverOptions['classname'],
                        $driverOptions['ctorargs'] ?? []
                    );
                    break;

                default:
                    $pdoStatement = $this->pdo->query(
                        $sql,
                        $driverOptions['fetchMode']
                    );
            }

            return new PdoQueryResult($this->handleFalse($pdoStatement));
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * @param   PhpPdoStatement<mixed>|false  $pdoStatement
     * @return  PhpPdoStatement<mixed>
     */
    private function handleFalse(PhpPdoStatement|false $pdoStatement): PhpPdoStatement
    {
        if (false === $pdoStatement) {
            throw new DatabaseException('An unknown error occurred.');
        }

        return $pdoStatement;
    }

    /**
     * execute an SQL statement and return the number of affected rows
     *
     * @throws  DatabaseException
     */
    public function exec(string $statement): int
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        try {
            return $this->pdo->exec($statement);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * @throws  DatabaseException
     */
    public function getLastInsertId(?string $name = null): string
    {
        if (null === $this->pdo) {
            throw new DatabaseException('Not connected: can not retrieve last insert id');
        }

        try {
            return $this->pdo->lastInsertId($name);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }
}
