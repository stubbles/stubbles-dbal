<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\config;
/**
 * Configuration for a database connection.
 */
class DatabaseConfiguration
{
    /**
     * id of the default connection
     */
    const DEFAULT_ID         = 'default';
    /**
     * id to use for the connection
     *
     * @type  string
     */
    private $id;
    /**
     * Data Source Name, or DSN, contains the information required to connect to the database
     *
     * @type  string
     */
    private $dsn;
    /**
     * user name
     *
     * @type  string
     */
    private $userName;
    /**
     * password
     *
     * @type  string
     */
    private $password;
    /**
     * a key=>value array of driver-specific connection options
     *
     * @type  array
     */
    private $driverOptions   = array();
    /**
     * initial query to be executed after commit
     *
     * @type  string
     */
    private $initialQuery;

    /**
     * create connection data instance from an array
     *
     * Please note that this method does not support driver options. Driver
     * options must be set separately.
     *
     * @param   string  $id
     * @param   string  $dsn
     * @param   array   $properties
     * @return  DatabaseConfiguration
     */
    public static function fromArray($id, $dsn, array $properties)
    {
        $self = new self($id, $dsn);
        if (isset($properties['username'])) {
            $self->userName = $properties['username'];
        }

        if (isset($properties['password'])) {
            $self->password = $properties['password'];
        }

        if (isset($properties['initialQuery'])) {
            $self->initialQuery = $properties['initialQuery'];
        }

        return $self;
    }

    /**
     * constructor
     *
     * @param  string  $id   id of connection
     * @param  string  $dsn  data source name
     */
    public function __construct($id, $dsn)
    {
        $this->id  = $id;
        $this->dsn = $dsn;
    }

    /**
     * return the id to use for the connection
     *
     * Warning: two instances will be the same if they have the same id,
     * regardless whether the concrete connection data is differant or not.
     * You should never use the same id for differant connection datasets.
     *
     * @return  string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * returns the Data Source Name
     *
     * @return  string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * sets user name for database login
     *
     * @param   string  $userName
     * @return  DatabaseConfiguration
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * returns the user name
     *
     * @return  string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * sets user password for database login
     *
     * @param   string  $password
     * @return  DatabaseConfiguration
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * returns the user password
     *
     * @return  string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * sets driver-specific connection options for database
     *
     * @param   array  $driverOptions
     * @return  DatabaseConfiguration
     */
    public function setDriverOptions(array $driverOptions)
    {
        $this->driverOptions = $driverOptions;
        return $this;
    }

    /**
     * checks if any driver-specific connection options are present
     *
     * @return  bool
     */
    public function hasDriverOptions()
    {
        return (count($this->driverOptions) > 0);
    }

    /**
     * returns a key=>value array of driver-specific connection options
     *
     * @return  array
     */
    public function getDriverOptions()
    {
        return $this->driverOptions;
    }

    /**
     * sets initial query to be send after establishing the connection
     *
     * @param   string  $initialQuery
     * @return  DatabaseConfiguration
     */
    public function setInitialQuery($initialQuery)
    {
        $this->initialQuery = $initialQuery;
        return $this;
    }

    /**
     * checks if an initial query should be send
     *
     * @return  string
     */
    public function hasInitialQuery()
    {
        return (null != $this->initialQuery);
    }

    /**
     * returns initial query to be send after establishing the connection
     *
     * @return  string
     */
    public function getInitialQuery()
    {
        return $this->initialQuery;
    }
}
