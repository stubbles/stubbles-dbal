<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;

use IteratorAggregate;
use stubbles\ioc\InjectionProvider;
use stubbles\sequence\iterator\MappingIterator;
use Traversable;

/**
 * IoC provider for database instances.
 *
 * @since  2.1.0
 * @implements  \IteratorAggregate<Database>
 * @implements  InjectionProvider<Database>
 */
class Databases implements IteratorAggregate, InjectionProvider
{
    public function __construct(private DatabaseConnections $connections) { }

    /**
     * returns the database
     *
     * If a name is provided and a connection with this name exists this
     * connection will be used. If fallback is enabled and the named
     * connection does not exist the default connection will be used, if
     * fallback is disabled a \OutOfBoundsException will be thrown.
     *
     * If no name is provided the default connection will be used.
     */
    public function get(string $name = null): Database
    {
        return new Database($this->connections->get($name));
    }

    /**
     * returns an external iterator
     *
     * @return  \Iterator<Database>
     */
    public function getIterator(): Traversable
    {
        return new MappingIterator(
            $this->connections,
            fn($_, string $key): Database => $this->get($key)
        );
    }
}
