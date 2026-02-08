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
    public const string DEFAULT_ID = 'default';
    private ?string $userName = null;
    private ?Secret $password = null;
    /**
     * a key=>value array of driver-specific connection options
     *
     * @var  array<string,mixed>
     */
    private array $driverOptions   = [];
    /**
     * initial query to be executed after commit
     */
    private string $initialQuery = '';
    /**
     * some details about the database
     */
    private ?string $details = null;
    /**
     * list of other properties for this connection
     *
     * @var  array<string,mixed>
     */
    private array $properties = [];

    /**
     * create connection data instance from an array
     *
     * Please note that this method does not support driver options. Driver
     * options must be set separately.
     *
     * @param  array<string,mixed>  $properties
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
    public function __construct(private string $id, private string $dsn) { }

    /**
     * return the id to use for the connection
     *
     * Warning: two instances will be the same if they have the same id,
     * regardless whether the concrete connection data is differant or not.
     * You should never use the same id for differant connection datasets.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * returns the Data Source Name
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * sets user name for database login
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * returns the user name
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * sets user password for database login
     */
    public function setPassword(Secret $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * returns the user password
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
     * @param  array<string,mixed>  $driverOptions
     */
    public function setDriverOptions(array $driverOptions): self
    {
        $this->driverOptions = $driverOptions;
        return $this;
    }

    /**
     * checks if any driver-specific connection options are present
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
     */
    public function setInitialQuery(string $initialQuery): self
    {
        $this->initialQuery = $initialQuery;
        return $this;
    }

    /**
     * checks if an initial query should be send
     */
    public function hasInitialQuery(): bool
    {
        return null != $this->initialQuery;
    }

    /**
     * returns initial query to be send after establishing the connection
     */
    public function getInitialQuery(): string
    {
        return $this->initialQuery;
    }

    /**
     * sets details about the database
     *
     * @since  2.1.0
     */
    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * returns details about the database
     *
     * @since  2.1.0
     */
    public function getDetails(): ?string
    {
        return $this->details;
    }

    /**
     * returns property with given name or given default if property not set
     *
     * @since  2.2.0
     */
    public function getProperty(string $name, mixed $default = null)
    {
        return $this->properties[$name] ?? $default;
    }
}
