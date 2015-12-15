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
use org\bovigo\vfs\vfsStream;

use function stubbles\lang\reflect\annotationsOf;
use function stubbles\lang\reflect\annotationsOfConstructorParameter;
/**
 * Test for stubbles\db\config\PropertyBasedDatabaseConfigurations.
 *
 * @group  db
 * @group  config
 */
class PropertyBasedDatabaseConfigurationsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\db\config\PropertyBasedDatabaseConfigurations
     */
    private $propertyBasedConfigurations;
    /**
     *
     * @type  \org\bovigo\vfs\vfsStreamFile
     */
    private $configFile;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations(
                $this->createConfigFolder()
        );
    }

    /**
     * creates config folder and returns its url
     *
     * @param   string  name of config file
     * @return  string
     */
    private function createConfigFolder($filename = 'rdbms.ini')
    {
        $root = vfsStream::setup();
        $this->configFile = vfsStream::newFile($filename)->at($root);
        return $root->url();
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        assertTrue(
                annotationsOf($this->propertyBasedConfigurations)
                        ->contain('Singleton')
        );
    }

    /**
     * @return  array
     */
    public function annotatedParameters()
    {
        return [
            ['configPath', 'stubbles.config.path'],
            ['descriptor', 'stubbles.db.descriptor'],
            ['fallback', 'stubbles.db.fallback'],
        ];
    }

    /**
     * @test
     * @dataProvider  annotatedParameters
     */
    public function annotationsPresentOnConstructor($parameterName, $expectedName)
    {
        $annotations = annotationsOfConstructorParameter(
                $parameterName,
                $this->propertyBasedConfigurations
        );
        assertTrue($annotations->contain('Named'));
        assertEquals(
                $expectedName,
                $annotations->firstNamed('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function isDefaultImplementationForDatabaseInitializerInterface()
    {
        assertEquals(
                get_class($this->propertyBasedConfigurations),
                annotationsOf(DatabaseConfigurations::class)
                        ->firstNamed('ImplementedBy')->__value()->getName()
        );
    }

    /**
     * @test
     */
    public function containsConfigWhenPresentInFile()
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assertTrue($this->propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function containsConfigWhenNotPresentInFileButDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        assertTrue($this->propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function doesNotContainConfigWhenNotPresentInFileAndNoDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        assertFalse($this->propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function doesNotContainConfigWhenNotPresentInFileAndFallbackDisabled()
    {
        $propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations(
                $this->createConfigFolder() ,
                'rdbms',
                false
        );
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        assertFalse(
                $propertyBasedConfigurations->contain('foo')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\ConfigurationException
     * @expectedExceptionMessage  Missing dsn property in database configuration with id foo
     */
    public function throwsConfigurationExceptionWhenDsnPropertyMissing()
    {
        $this->configFile->setContent('[foo]
username="root"');
        $this->propertyBasedConfigurations->get('foo');
    }

    /**
     * @test
     */
    public function returnsConfigWhenPresentInFile()
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assertEquals(
                'foo',
                $this->propertyBasedConfigurations->get('foo')->getId()
        );
    }

    /**
     * @test
     */
    public function returnsDefaultConfigWhenNotPresentInFileButDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        assertEquals(
                'default',
                $this->propertyBasedConfigurations->get('foo')->getId()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\ConfigurationException
     * @expectedExceptionMessage  No database configuration known for database requested with id foo
     */
    public function throwsConfigurationExceptionWhenNotPresentInFileAndNoDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        $this->propertyBasedConfigurations->get('foo');
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\ConfigurationException
     * @expectedExceptionMessage  No database configuration known for database requested with id foo
     */
    public function throwsConfigurationExceptionWhenNotPresentInFileAndFallbackDisabled()
    {
        $propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations(
                $this->createConfigFolder() ,
                'rdbms',
                false
        );
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $propertyBasedConfigurations->get('foo');
    }

    /**
     * @test
     */
    public function usesDifferentFileWhenDescriptorChanged()
    {
        $propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations(
                $this->createConfigFolder('rdbms-test.ini'),
                'rdbms-test'
        );
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assertEquals('mysql:host=localhost;dbname=example',
                            $propertyBasedConfigurations->get('foo')
                                                        ->getDsn()
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function canIterateOverConfigurations()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"

[other]
dsn="mysql:host=example.com;dbname=other"');
        $result = [];
        foreach ($this->propertyBasedConfigurations as $configuration) {
            $result[] = $configuration->getId();
        }

        assertEquals(['default', 'other'], $result);
    }
}
