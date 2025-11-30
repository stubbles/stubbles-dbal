<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;

use IteratorAggregate;
use LogicException;
use OutOfBoundsException;
use stubbles\sequence\iterator\MappingIterator;
use stubbles\values\Properties;
use Traversable;

/**
 * Represents a list of available database configurations, configured in a property file.
 *
 * @Singleton
 * @implements IteratorAggregate<DatabaseConfiguration>
 */
class PropertyBasedDatabaseConfigurations implements IteratorAggregate, DatabaseConfigurations
{
    private ?Properties $dbProperties = null;

    /**
     * constructor
     *
     * @param  string  $configPath
     * @Named{configPath}('stubbles.config.path')
     * @Named{descriptor}('stubbles.db.descriptor')
     * @Named{fallback}('stubbles.db.fallback')
     */
    public function  __construct(
        private string $configPath,
        private string $descriptor = 'rdbms',
        private bool $fallback = true
    ) { }

    /**
     * checks whether fallback is enabled and exists
     */
    private function hasFallback(): bool
    {
        return $this->fallback
            && $this->properties()->containSection(DatabaseConfiguration::DEFAULT_ID)
        ;
    }

    /**
     * checks whether database configuration for given id exists
     */
    public function contain(string $id): bool
    {
        if ($this->properties()->containSection($id)) {
            return true;
        }

        return $this->hasFallback();
    }

    /**
     * returns database configuration with given id
     *
     * @throws  OutOfBoundsException  in case no configuration for given id is found and fallback is disabled
     * @throws  LogicException  in case the found configuration misses the dsn property
     */
    public function get(string $id): DatabaseConfiguration
    {
        if (!$this->properties()->containSection($id)) {
            if (!$this->hasFallback()) {
                throw new OutOfBoundsException(
                    'No database configuration known for database requested with id ' . $id
                );
            }

            $id = DatabaseConfiguration::DEFAULT_ID;
        }

        if (!$this->properties()->containValue($id, 'dsn')) {
            throw new LogicException(
                'Missing dsn property in database configuration with id ' . $id
            );
        }

        return DatabaseConfiguration::fromArray(
            $id,
            (string) $this->properties()->value($id, 'dsn'),
            $this->properties()->section($id)
        );
    }

    /**
     * reads properties if not done yet
     */
    protected function properties(): Properties
    {
        if (null === $this->dbProperties) {
            $this->dbProperties = Properties::fromFile(
                $this->configPath . '/' . $this->descriptor . '.ini'
            );
        }

        return $this->dbProperties;
    }

    /**
     * returns an external iterator
     *
     * @return  \Iterator<DatabaseConfiguration>
     */
    public function getIterator(): Traversable
    {
        return new MappingIterator(
            $this->properties(),
            fn($_, string $key): DatabaseConfiguration => $this->get($key)
        );
    }
}
