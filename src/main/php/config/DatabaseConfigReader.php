<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\config;
/**
 * Interface for database configuration readers.
 *
 * @ImplementedBy(net\stubbles\db\config\PropertyBasedDatabaseConfigReader.class)
 */
interface DatabaseConfigReader
{
    /**
     * sets the descriptor to be used
     *
     * @param   string  $descriptor
     * @return  DatabaseConfigReader
     */
    public function setDescriptor($descriptor);

    /**
     * whether to fallback to default database if requested database id does not exist
     *
     * @param   bool  $fallback
     * @return  DatabaseConfigReader
     */
    public function setFallback($fallback);

    /**
     * returns list of available config ids
     *
     * @return  string[]
     * @since   2.1.0
     */
    public function configIds();

    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
     */
    public function hasConfig($id);

    /**
     * returns database configuration with given id
     *
     * @param   string                      $id
     * @return  DatabaseConfiguration
     */
    public function readConfig($id);
}
