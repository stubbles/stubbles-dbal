<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\config;
use stubbles\lang\exception\ConfigurationException;
/**
 * List of available database configurations, provided as array.
 *
 * @since  4.0.0
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
    public function __construct($configurations)
    {
        $this->configurations = $configurations;
    }

    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
     */
    public function contain($id)
    {
        return isset($this->configurations[$id]);
    }

    /**
     * returns database configuration for given id
     *
     * @param   string  $id  id of configuration to return
     * @return  \stubbles\db\config\DatabaseConfiguration
     * @throws  \stubbles\lang\exception\ConfigurationException  in case no config for given id exists
     */
    public function get($id)
    {
        if (isset($this->configurations[$id])) {
            return $this->configurations[$id];
        }

        throw new ConfigurationException('No database configuration known for database requested with id ' . $id);
    }

    /**
     * returns an external iterator
     *
     * @return  \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->configurations);
    }
}
