<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\ioc;
use net\stubbles\db\config\DatabaseConfiguration;
use net\stubbles\db\config\DatabaseConfigReader;
use net\stubbles\db\pdo\PdoDatabaseConnection;
use stubbles\ioc\InjectionProvider;
use stubbles\lang\exception\ConfigurationException;
/**
 * IoC provider for database connections.
 */
class ConnectionProvider implements InjectionProvider
{
    /**
     * database configuration reader
     *
     * @type  DatabaseConfigReader
     */
    private $configReader;
    /**
     * map of database connections
     *
     * @type  array
     */
    private $connections  = [];

    /**
     * constructor
     *
     * @param  DatabaseConfigReader  $configReader
     * @Inject
     */
    public function __construct(DatabaseConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * returns list of available connection ids
     *
     * @return  string[]
     */
    public function availableConnections()
    {
        return $this->configReader->configIds();
    }

    /**
     * returns the connection to be injected
     *
     * If a name is provided and a connection with this name exists this
     * connection will be returned. If fallback is enabled and the named
     * connection does not exist the default connection will be returned, if
     * fallback is disabled a DatabaseException will be thrown.
     *
     * If no name is provided the default connection will be returned.
     *
     * @param   string  $name
     * @return  DatabaseConnection
     */
    public function get($name = null)
    {
        if (null == $name) {
            $name = DatabaseConfiguration::DEFAULT_ID;
        }

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $this->connections[$name] = new PdoDatabaseConnection($this->readConfig($name));
        return $this->connections[$name];
    }

    /**
     * retrieves connection data
     *
     * @param   string  $name
     * @return  ConnectionConfiguration
     * @throws  ConfigurationException
     */
    private function readConfig($name)
    {
        if ($this->configReader->hasConfig($name)) {
            return $this->configReader->readConfig($name);
        }

        throw new ConfigurationException('No database configuration known for database requested with id ' . $name);
    }
}
