<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;
use stubbles\sequence\iterator\MappingIterator;
use stubbles\values\Properties;
/**
 * Represents a list of available database configurations, configured in a property file.
 *
 * @Singleton
 * @implements \IteratorAggregate<DatabaseConfiguration>
 */
class PropertyBasedDatabaseConfigurations implements \IteratorAggregate, DatabaseConfigurations
{
    /**
     * path to config files
     *
     * @type  string
     */
    private $configPath;
    /**
     * descriptor to be used
     *
     * @type  string
     */
    private $descriptor;
    /**
     * switch whether to fallback to default connection if no named connection exists
     *
     * @type  bool
     */
    private $fallback;
    /**
     * properties for database connections
     *
     * @type  \stubbles\lang\Properties
     */
    private $dbProperties;

    /**
     * constructor
     *
     * @param  string  $configPath
     * @Named{configPath}('stubbles.config.path')
     * @Named{descriptor}('stubbles.db.descriptor')
     * @Named{fallback}('stubbles.db.fallback')
     */
    public function  __construct(
            string $configPath,
            string $descriptor = 'rdbms',
            bool $fallback = true
    ) {
        $this->configPath = $configPath;
        $this->descriptor = $descriptor;
        $this->fallback   = $fallback;
    }

    /**
     * checks whether fallback is enabled and exists
     *
     * @return bool
     */
    private function hasFallback(): bool
    {
        return ($this->fallback
                && $this->properties()->containSection(DatabaseConfiguration::DEFAULT_ID)
        );
    }

    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
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
     * @param   string  $id
     * @return  \stubbles\db\config\DatabaseConfiguration
     * @throws  \OutOfBoundsException  in case no configuration for given id is found and fallback is disabled
     * @throws  \LogicException  in case the found configuration misses the dsn property
     */
    public function get(string $id)
    {
        if (!$this->properties()->containSection($id)) {
            if (!$this->hasFallback()) {
                throw new \OutOfBoundsException('No database configuration known for database requested with id ' . $id);
            }

            $id = DatabaseConfiguration::DEFAULT_ID;
        }

        if (!$this->properties()->containValue($id, 'dsn')) {
            throw new \LogicException('Missing dsn property in database configuration with id ' . $id);
        }

        return DatabaseConfiguration::fromArray(
                $id,
                (string) $this->properties()->value($id, 'dsn'),
                $this->properties()->section($id)
        );
    }

    /**
     * reads properties if not done yet
     *
     * @return  \stubbles\values\Properties
     */
    protected function properties(): Properties
    {
        if (null === $this->dbProperties) {
            $this->dbProperties = Properties::fromFile($this->configPath . '/' . $this->descriptor . '.ini');
        }

        return $this->dbProperties;
    }

    /**
     * returns an external iterator
     *
     * @return  \Iterator<DatabaseConfiguration>
     */
    public function getIterator(): \Iterator
    {
        return new MappingIterator(
                $this->properties(),
                function($value, $key)
                {
                    return $this->get($key);
                }
        );
    }
}
