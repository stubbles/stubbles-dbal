<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\pdo;
use net\stubbles\db\DatabaseConnection;
use net\stubbles\db\DatabaseException;
use net\stubbles\db\config\DatabaseConfiguration;
use net\stubbles\lang\exception\IllegalArgumentException;
use net\stubbles\lang\exception\MethodInvocationException;
use net\stubbles\lang\exception\RuntimeException;
use PDO;
use PDOException;
/**
 * Wrapper around the pdo connection.
 *
 * @see  http://php.net/pdo
 */
class PdoDatabaseConnection implements DatabaseConnection
{
    /**
     * database configuration required to establish the connection
     *
     * @type  DatabaseConfiguration
     */
    private $configuration;
    /**
     * closure to create a pdo instance
     *
     * @type  \Closure
     */
    private $pdoCreator;
    /**
     * instance of pdo
     *
     * @type  \PDO
     */
    private  $pdo           = null;

    /**
     * constructor
     *
     * @param   DatabaseConfiguration  $configuration  database configuration required to establish the connection
     * @throws  RuntimeException
     */
    public function __construct(DatabaseConfiguration $configuration, \Closure $pdoCreator = null)
    {
        if (!extension_loaded('pdo')) {
            throw new RuntimeException('Can not create ' . __CLASS__ . ', requires PHP-extension "pdo".');
        }

        $this->configuration = $configuration;
        $this->pdoCreator    = $pdoCreator;
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * establishes the connection
     *
     * @throws  DatabaseException
     */
    public function connect()
    {
        if (null !== $this->pdo) {
            return;
        }

        try {
            $pdoCreator = $this->getPdoCreator();
            $this->pdo  = $pdoCreator($this->configuration);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if ($this->configuration->hasInitialQuery()) {
                $this->pdo->query($this->configuration->getInitialQuery());
            }
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     *
     * @return  \Closure
     */
    private function getPdoCreator()
    {
        if (null !== $this->pdoCreator) {
            return $this->pdoCreator;
        }

        return function(DatabaseConfiguration $configuration)
               {
                   if (!$configuration->hasDriverOptions()) {
                       return new PDO($configuration->getDsn(),
                                      $configuration->getUserName(),
                                      $configuration->getPassword()
                       );
                   }

                   return new PDO($configuration->getDsn(),
                                  $configuration->getUserName(),
                                  $configuration->getPassword(),
                                  $configuration->getDriverOptions()
                   );
               };
    }

    /**
     * disconnects the database
     */
    public function disconnect()
    {
        $this->pdo = null;
    }

    /**
     * redirects calls on non-existing methods to the pdo object
     *
     * @param   string  $method     name of the method to call
     * @param   array   $arguments  list of arguments for the method call
     * @return  mixed
     * @throws  DatabaseException
     * @throws  MethodInvocationException
     */
    public function __call($method, $arguments)
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        if (!method_exists($this->pdo, $method)) {
            throw new MethodInvocationException('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
        }

        try {
            return call_user_func_array(array($this->pdo, $method), $arguments);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }

    /**
     * start a transaction
     *
     * @return  bool
     */
    public function beginTransaction()
    {
        return $this->__call('beginTransaction', array());
    }

    /**
     * commit a transaction
     *
     * @return  bool
     */
    public function commit()
    {
        return $this->__call('commit', array());
    }

    /**
     * rollback a transaction
     *
     * @return  bool
     */
    public function rollback()
    {
        return $this->__call('rollBack', array());
    }

    /**
     * creates a prepared statement
     *
     * @param   string  $statement      SQL statement
     * @param   array   $driverOptions  optional  one or more key=>value pairs to set attribute values for the Statement object
     * @return  PdoStatement
     * @throws  DatabaseException
     * @see     http://php.net/pdo-prepare
     */
    public function prepare($statement, array $driverOptions = array())
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        try {
            $statement = new PdoStatement($this->pdo->prepare($statement, $driverOptions));
            return $statement;
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
     * ctorargs  => (optional) if fetchMode == PDO::FETCH_CLASS this denotes the list of arguments for the constructor of the class to create and fetch the data into
     * </code>
     *
     * @param   string  $sql            the sql query to use
     * @param   array   $driverOptions  optional  how to fetch the data
     * @return  PdoQueryResult
     * @throws  IllegalArgumentException
     * @throws  DatabaseException
     * @see     http://php.net/pdo-query
     * @see     http://php.net/pdostatement-setfetchmode for the details on the fetch mode options
     */
    public function query($sql, array $driverOptions = array())
    {
        if (null === $this->pdo) {
            $this->connect();
        }

        try {
            if (isset($driverOptions['fetchMode'])) {
                switch ($driverOptions['fetchMode']) {
                    case PDO::FETCH_COLUMN:
                        if (!isset($driverOptions['colNo'])) {
                            throw new IllegalArgumentException('Fetch mode COLUMN requires driver option colNo.');
                        }

                        $pdoStatement = $this->pdo->query($sql, $driverOptions['fetchMode'], $driverOptions['colNo']);
                        break;

                    case PDO::FETCH_INTO:
                        if (!isset($driverOptions['object'])) {
                            throw new IllegalArgumentException('Fetch mode INTO requires driver option object.');
                        }

                        $pdoStatement = $this->pdo->query($sql, $driverOptions['fetchMode'], $driverOptions['object']);
                        break;

                    case PDO::FETCH_CLASS:
                        if (!isset($driverOptions['classname'])) {
                            throw new IllegalArgumentException('Fetch mode CLASS requires driver option classname.');
                        }

                        if (!isset($driverOptions['ctorargs'])) {
                            $driverOptions['ctorargs'] = array();
                        }

                        $pdoStatement = $this->pdo->query($sql, $driverOptions['fetchMode'], $driverOptions['classname'], $driverOptions['ctorargs']);
                        break;

                    default:
                        $pdoStatement = $this->pdo->query($sql, $driverOptions['fetchMode']);
                }
            } else {
                $pdoStatement = $this->pdo->query($sql);
            }
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }

        return new PdoQueryResult($pdoStatement);
    }

    /**
     * execute an SQL statement and return the number of affected rows
     *
     * @param   string  $statement      the sql statement to execute
     * @param   array   $driverOptions  optional  one or more driver specific options for the call to query()
     * @return  int     number of effected rows
     * @throws  DatabaseException
     */
    public function exec($statement, array $driverOptions = array())
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
     * returns the last insert id
     *
     * @param   string  $name  name of the sequence object from which the ID should be returned.
     * @return  int
     * @throws  DatabaseException
     */
    public function getLastInsertId($name = null)
    {
        if (null === $this->pdo) {
            throw new DatabaseException('Not connected: can not retrieve last insert id.');
        }

        try {
            return $this->pdo->lastInsertId($name);
        } catch (PDOException $pdoe) {
            throw new DatabaseException($pdoe->getMessage(), $pdoe);
        }
    }
}
