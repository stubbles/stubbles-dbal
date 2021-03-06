<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;
/**
 * List of available database configurations, provided as array.
 *
 * @since  4.0.0
 * @implements  \IteratorAggregate<DatabaseConfiguration>
 */
class ArrayBasedDatabaseConfigurations implements \IteratorAggregate, DatabaseConfigurations
{
    /**
     * map of available configurations
     *
     * @type  \stubbles\db\config\DatabaseConfiguration[]
     */
    private $configurations;

    /**
     * constructor
     *
     * @param  \stubbles\db\config\DatabaseConfiguration[]  $configurations  map of configurations which should be available
     */
    public function __construct(array $configurations)
    {
        $this->configurations = $configurations;
    }

    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
     */
    public function contain(string $id): bool
    {
        return isset($this->configurations[$id]);
    }

    /**
     * returns database configuration for given id
     *
     * @param   string  $id  id of configuration to return
     * @return  \stubbles\db\config\DatabaseConfiguration
     * @throws  \OutOfBoundsException  in case no config for given id exists
     */
    public function get(string $id): DatabaseConfiguration
    {
        if (isset($this->configurations[$id])) {
            return $this->configurations[$id];
        }

        throw new \OutOfBoundsException(
                'No database configuration known for database requested with id '
                . $id
        );
    }

    /**
     * returns an external iterator
     *
     * @return  \Iterator<DatabaseConfiguration>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->configurations);
    }
}
