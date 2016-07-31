<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\config;
/**
 * Represents a list of available database configurations.
 *
 * @ImplementedBy(stubbles\db\config\PropertyBasedDatabaseConfigurations.class)
 */
interface DatabaseConfigurations extends \Traversable
{
    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
     */
    public function contain(string $id): bool;

    /**
     * returns database configuration for given id
     *
     * @param   string  $id
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public function get(string $id);
}
