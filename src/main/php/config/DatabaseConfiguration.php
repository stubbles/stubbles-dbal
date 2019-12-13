<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;
use stubbles\values\Secret;
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
     * @var  string
     */
    private $id;
    /**
     * Data Source Name, or DSN, contains the information required to connect to the database
     *
     * @var  string
     */
    private $dsn;
    /**
     * user name
     *
     * @var  string
     */
    private $userName;
    /**
     * password
     *
     * @var  \stubbles\values\Secret
     */
    private $password;
    /**
     * a key=>value array of driver-specific connection options
     *
     * @var  array<string,mixed>
     */
    private $driverOptions   = [];
    /**
     * initial query to be executed after commit
     *
     * @var  string
     */
    private $initialQuery = '';
    /**
     * some details about the database
     *
     * @var  string
     */
    private $details;
    /**
     * list of other properties for this connection
     *
     * @var  array<string,mixed>
     */
    private $properties = [];

    /**
     * create connection data instance from an array
     *
     * Please note that this method does not support driver options. Driver
     * options must be set separately.
     *
     * @param   string               $id
     * @param   string               $dsn
     * @param   array<string,mixed>  $properties
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public static function fromArray(string $id, string $dsn, array $properties): DatabaseConfiguration
    {
        $self = new self($id, $dsn);
        if (isset($properties['username'])) {
            $self->userName = $properties['username'];
            unset($properties['username']);
        }

        if (isset($properties['password'])) {
            $self->setPassword(Secret::create($properties['password']));
            unset($properties['password']);
        }

        if (isset($properties['initialQuery'])) {
            $self->initialQuery = $properties['initialQuery'];
            unset($properties['initialQuery']);
        }

        if (isset($properties['details'])) {
            $self->details = $properties['details'];
            unset($properties['details']);
        }

        $self->properties = $properties;
        return $self;
    }

    /**
     * constructor
     *
     * @param  string  $id   id of connection
     * @param  string  $dsn  data source name
     */
    public function __construct(string $id, string $dsn)
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
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * returns the Data Source Name
     *
     * @return  string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * sets user name for database login
     *
     * @param   string  $userName
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * returns the user name
     *
     * @return  string
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * sets user password for database login
     *
     * @param   \stubbles\values\Secret  $password
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public function setPassword(Secret $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * returns the user password
     *
     * @return  string
     */
    public function getPassword(): ?string
    {
        if (null !== $this->password) {
            return $this->password->unveil();
        }

        return null;
    }

    /**
     * sets driver-specific connection options for database
     *
     * @param   array<string,mixed>  $driverOptions
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public function setDriverOptions(array $driverOptions): self
    {
        $this->driverOptions = $driverOptions;
        return $this;
    }

    /**
     * checks if any driver-specific connection options are present
     *
     * @return  bool
     */
    public function hasDriverOptions(): bool
    {
        return (count($this->driverOptions) > 0);
    }

    /**
     * returns a key=>value array of driver-specific connection options
     *
     * @return  array<string,mixed>
     */
    public function getDriverOptions(): array
    {
        return $this->driverOptions;
    }

    /**
     * sets initial query to be send after establishing the connection
     *
     * @param   string  $initialQuery
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public function setInitialQuery(string $initialQuery): self
    {
        $this->initialQuery = $initialQuery;
        return $this;
    }

    /**
     * checks if an initial query should be send
     *
     * @return  bool
     */
    public function hasInitialQuery(): bool
    {
        return (null != $this->initialQuery);
    }

    /**
     * returns initial query to be send after establishing the connection
     *
     * @return  string
     */
    public function getInitialQuery(): string
    {
        return $this->initialQuery;
    }

    /**
     * sets details about the database
     *
     * @param   string  $details
     * @return  \stubbles\db\config\DatabaseConfiguration
     * @since   2.1.0
     */
    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * returns details about the database
     *
     * @return  string
     * @since   2.1.0
     */
    public function getDetails(): ?string
    {
        return $this->details;
    }

    /**
     * returns property with given name or given default if property not set
     *
     * @param   string  $name
     * @param   string  $default  optional  value to return if property not set
     * @return  string
     * @since   2.2.0
     */
    public function getProperty(string $name, $default = null)
    {
        return $this->properties[$name] ?? $default;
    }
}
