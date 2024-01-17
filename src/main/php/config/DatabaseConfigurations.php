<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;

use Traversable;

/**
 * Represents a list of available database configurations.
 *
 * @ImplementedBy(stubbles\db\config\PropertyBasedDatabaseConfigurations.class)
 * @extends  Traversable<string,DatabaseConfiguration>
 */
interface DatabaseConfigurations extends Traversable
{
    /**
     * checks whether database configuration for given id exists
     */
    public function contain(string $id): bool;

    /**
     * returns database configuration for given id
     */
    public function get(string $id): DatabaseConfiguration;
}
