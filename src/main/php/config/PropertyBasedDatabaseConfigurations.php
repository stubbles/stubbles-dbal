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
use stubbles\lang\Properties;
use stubbles\lang\exception\ConfigurationException;
use stubbles\lang\iterator\PropertyBasedIterator;
/**
 * Represents a list of available database configurations, configured in a property file.
 *
 * @Singleton
 */
class PropertyBasedDatabaseConfigurations implements \Iterator, DatabaseConfigurations
{
    use PropertyBasedIterator;

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
    private $descriptor   = 'rdbms';
    /**
     * switch whether to fallback to default connection if no named connection exists
     *
     * @type  bool
     */
    private $fallback     = true;
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
     * @Inject
     * @Named('stubbles.config.path')
     */
    public function  __construct($configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * sets the descriptor to be used
     *
     * @param   string  $descriptor
     * @return  \stubbles\db\config\PropertyBasedDatabaseConfigurations
     * @Inject(optional=true)
     * @Named('stubbles.db.descriptor')
     */
    public function setDescriptor($descriptor)
    {
        $this->descriptor = $descriptor;
        return $this;
    }

    /**
     * whether to fallback to default database if requested database id does not exist
     *
     * @param   bool  $fallback
     * @return  \stubbles\db\config\PropertyBasedDatabaseConfigurations
     * @Inject(optional=true)
     * @Named('stubbles.db.fallback')
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * checks whether fallback is enabled and exists
     *
     * @return bool
     */
    private function hasFallback()
    {
        return ($this->fallback && $this->properties()->containSection(DatabaseConfiguration::DEFAULT_ID));
    }

    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
     */
    public function contain($id)
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
     * @throws  \stubbles\lang\exception\ConfigurationException
     */
    public function get($id)
    {
        if (!$this->properties()->containSection($id)) {
            if (!$this->hasFallback()) {
                throw new ConfigurationException('No database configuration known for database requested with id ' . $id);
            }

            $id = DatabaseConfiguration::DEFAULT_ID;
        }

        if (!$this->properties()->containValue($id, 'dsn')) {
            throw new ConfigurationException('Missing dsn property in database configuration with id ' . $id);
        }

        return DatabaseConfiguration::fromArray(
                $id,
                $this->properties()->value($id, 'dsn'),
                $this->properties()->section($id)
        );
    }

    /**
     * reads properties if not done yet
     *
     * @return  \stubbles\lang\Properties
     */
    protected function properties()
    {
        if (null === $this->dbProperties) {
            $this->dbProperties = Properties::fromFile($this->configPath . '/' . $this->descriptor . '.ini');
        }

        return $this->dbProperties;
    }

    /**
     * returns current entry in iteration
     *
     * @return  \stubbles\db\config\DatabaseConfiguration
     */
    public function current()
    {
        return $this->get($this->properties()->key());
    }
}
