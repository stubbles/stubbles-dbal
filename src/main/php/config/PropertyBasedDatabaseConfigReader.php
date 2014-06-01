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
/**
 * Creates database configuration instances based on property files.
 *
 * @Singleton
 */
class PropertyBasedDatabaseConfigReader implements DatabaseConfigReader
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
     * @type  Properties
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
     * @return  PropertyBasedDatabaseConfigReader
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
     * @return  PropertyBasedDatabaseConfigReader
     * @Inject(optional=true)
     * @Named('stubbles.db.fallback')
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * returns list of available config ids
     *
     * @return  string[]
     * @since   2.1.0
     */
    public function configIds()
    {
        return $this->readProperties()->getSections();
    }

    /**
     * checks whether database configuration for given id exists
     *
     * @param   string  $id
     * @return  bool
     */
    public function hasConfig($id)
    {
        if ($this->readProperties()->hasSection($id)) {
            return true;
        }

        return $this->hasFallback();
    }

    /**
     * returns database configuration with given id
     *
     * @param   string  $id
     * @return  DatabaseConfiguration
     * @throws  ConfigurationException
     */
    public function readConfig($id)
    {
        if (!$this->readProperties()->hasSection($id)) {
            if (!$this->hasFallback()) {
                return null;
            }

            $id = DatabaseConfiguration::DEFAULT_ID;
        }

        $properties = $this->readProperties()->getSection($id);
        if (!isset($properties['dsn'])) {
            throw new ConfigurationException('Missing dsn property in database configuration with id ' . $id);
        }

        return DatabaseConfiguration::fromArray($id, $properties['dsn'], $properties);
    }

    /**
     * checks whether fallback is enabled and exists
     *
     * @return bool
     */
    private function hasFallback()
    {
        return ($this->fallback && $this->readProperties()->hasSection(DatabaseConfiguration::DEFAULT_ID));
    }

    /**
     * initializing method
     *
     * @return  Properties
     */
    protected function readProperties()
    {
        if (null === $this->dbProperties) {
            $this->dbProperties = Properties::fromFile($this->configPath . '/' . $this->descriptor . '.ini');
        }

        return $this->dbProperties;
    }
}
