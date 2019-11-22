<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;
use stubbles\ioc\InjectionProvider;
use stubbles\sequence\iterator\MappingIterator;
/**
 * IoC provider for database instances.
 *
 * @since  2.1.0
 */
class Databases implements \IteratorAggregate, InjectionProvider
{
    /**
     * actual connection provider
     *
     * @type  \stubbles\db\DatabaseConnections
     */
    private $connections;

    /**
     * constructor
     *
     * @param  \stubbles\db\DatabaseConnections  $connections
     */
    public function __construct(DatabaseConnections $connections)
    {
        $this->connections = $connections;
    }

    /**
     * returns the database
     *
     * If a name is provided and a connection with this name exists this
     * connection will be used. If fallback is enabled and the named
     * connection does not exist the default connection will be used, if
     * fallback is disabled a DatabaseException will be thrown.
     *
     * If no name is provided the default connection will be used.
     *
     * @param   string  $name
     * @return  \stubbles\db\Database
     */
    public function get(string $name = null): Database
    {
        return new Database($this->connections->get($name));
    }

    /**
     * returns an external iterator
     *
     * @return  \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new MappingIterator(
                $this->connections,
                function($value, $key)
                {
                    return $this->get($key);
                }
        );
    }
}
