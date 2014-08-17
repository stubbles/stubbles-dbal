<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db;
use stubbles\db\config\DatabaseConfiguration;
use stubbles\db\config\DatabaseConfigurations;
use stubbles\db\pdo\PdoDatabaseConnection;
use stubbles\ioc\InjectionProvider;
use stubbles\lang\iterator\MappingIterator;
/**
 * List of available database connections.
 */
class DatabaseConnections implements \IteratorAggregate, InjectionProvider
{
    /**
     * database configuration reader
     *
     * @type  \stubbles\db\config\DatabaseConfigurations
     */
    private $configurations;
    /**
     * map of database connections
     *
     * @type  array
     */
    private $connections  = [];

    /**
     * constructor
     *
     * @param  \stubbles\db\config\DatabaseConfigurations  $configReader
     * @Inject
     */
    public function __construct(DatabaseConfigurations $configReader)
    {
        $this->configurations = $configReader;
    }

    /**
     * returns the connection
     *
     * If a name is provided and a connection with this name exists this
     * connection will be returned. If fallback is enabled and the named
     * connection does not exist the default connection will be returned, if
     * fallback is disabled a DatabaseException will be thrown.
     *
     * If no name is provided the default connection will be returned.
     *
     * @param   string  $name
     * @return  \stubbles\db\DatabaseConnection
     */
    public function get($name = null)
    {
        if (null == $name) {
            $name = DatabaseConfiguration::DEFAULT_ID;
        }

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $this->connections[$name] = new PdoDatabaseConnection($this->configurations->get($name));
        return $this->connections[$name];
    }

    /**
     * returns an external iterator
     *
     * @return  \Traversable
     */
    public function getIterator()
    {
        return new MappingIterator(
                $this->configurations,
                function($value, $key)
                {
                    return $this->get($key);
                }
        );
    }
}
