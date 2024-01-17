<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;

use ArrayIterator;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * List of available database configurations, provided as array.
 *
 * @since  4.0.0
 * @implements  \IteratorAggregate<DatabaseConfiguration>
 */
class ArrayBasedDatabaseConfigurations implements IteratorAggregate, DatabaseConfigurations
{
    /**
     * constructor
     *
     * @param  DatabaseConfiguration[]  $configurations  map of configurations which should be available
     */
    public function __construct(private array $configurations) { }

    /**
     * checks whether database configuration for given id exists
     */
    public function contain(string $id): bool
    {
        return isset($this->configurations[$id]);
    }

    /**
     * returns database configuration for given id
     *
     * @throws  OutOfBoundsException  in case no config for given id exists
     */
    public function get(string $id): DatabaseConfiguration
    {
        if (isset($this->configurations[$id])) {
            return $this->configurations[$id];
        }

        throw new OutOfBoundsException(
            'No database configuration known for database requested with id '
            . $id
        );
    }

    /**
     * returns an external iterator
     *
     * @return  \Iterator<DatabaseConfiguration>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->configurations);
    }
}
