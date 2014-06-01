<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\ioc;
use stubbles\db\Database;
use stubbles\ioc\InjectionProvider;
/**
 * IoC provider for database instances.
 *
 * @since  2.1.0
 */
class DatabaseProvider implements InjectionProvider
{
    /**
     * actual connection provider
     *
     * @type  ConnectionProvider
     */
    private $connectionProvider;

    /**
     * constructor
     *
     * @param  ConnectionProvider  $connectionProvider
     * @Inject
     */
    public function __construct(ConnectionProvider $connectionProvider)
    {
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * returns the database to be injected
     *
     * If a name is provided and a connection with this name exists this
     * connection will be used. If fallback is enabled and the named
     * connection does not exist the default connection will be used, if
     * fallback is disabled a DatabaseException will be thrown.
     *
     * If no name is provided the default connection will be used.
     *
     * @param   string  $name
     * @return  Database
     */
    public function get($name = null)
    {
        return new Database($this->connectionProvider->get($name));
    }
}
