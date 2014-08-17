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
use stubbles\lang;
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
        $root = vfsStream::setup();
        $this->configFile = vfsStream::newFile('rdbms.ini')->at($root);
        $this->propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations($root->url());
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue(
                lang\reflect($this->propertyBasedConfigurations)->hasAnnotation('Singleton')
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $class       = lang\reflect($this->propertyBasedConfigurations);
        $constructor = $class->getConstructor();
        $this->assertTrue($constructor->hasAnnotation('Inject'));
        $this->assertTrue($constructor->hasAnnotation('Named'));
        $this->assertEquals(
                'stubbles.config.path',
                $constructor->annotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetDescriptorMethod()
    {
        $setDescriptorMethod = lang\reflect($this->propertyBasedConfigurations)->getMethod('setDescriptor');
        $this->assertTrue($setDescriptorMethod->hasAnnotation('Inject'));
        $this->assertTrue($setDescriptorMethod->annotation('Inject')->isOptional());
        $this->assertTrue($setDescriptorMethod->hasAnnotation('Named'));
        $this->assertEquals(
                'stubbles.db.descriptor',
                $setDescriptorMethod->annotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetFallbackMethod()
    {
        $setFallbackMethod = lang\reflect($this->propertyBasedConfigurations)->getMethod('setFallback');
        $this->assertTrue($setFallbackMethod->hasAnnotation('Inject'));
        $this->assertTrue($setFallbackMethod->annotation('Inject')->isOptional());
        $this->assertTrue($setFallbackMethod->hasAnnotation('Named'));
        $this->assertEquals(
                'stubbles.db.fallback',
                $setFallbackMethod->annotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function isDefaultImplementationForDatabaseInitializerInterface()
    {
        $refClass = lang\reflect('stubbles\db\config\DatabaseConfigurations');
        $this->assertEquals(
                get_class($this->propertyBasedConfigurations),
                $refClass->annotation('ImplementedBy')->__value()->getName()
        );
    }

    /**
     * @test
     */
    public function containsConfigWhenPresentInFile()
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertTrue($this->propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function containsConfigWhenNotPresentInFileButDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertTrue($this->propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function doesNotContainConfigWhenNotPresentInFileAndNoDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertFalse($this->propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function doesNotContainConfigWhenNotPresentInFileAndFallbackDisabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertFalse(
                $this->propertyBasedConfigurations->setFallback(false)->contain('foo')
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
        $this->assertEquals(
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
        $this->assertEquals(
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
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->propertyBasedConfigurations->setFallback(false)->get('foo');
    }

    /**
     * @test
     */
    public function usesDifferentFileWhenDescriptorChanged()
    {
        $root = vfsStream::setup();
        vfsStream::newFile('rdbms-test.ini')
                 ->withContent('[foo]
dsn="mysql:host=localhost;dbname=example"')
                 ->at($root);
        $this->configFile->setContent('[foo]
dsn="mysql:host=prod.example.com;dbname=example"')
                         ->at($root);
        $this->assertEquals('mysql:host=localhost;dbname=example',
                            $this->propertyBasedConfigurations->setDescriptor('rdbms-test')
                                                            ->get('foo')
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

        $this->assertEquals(['default', 'other'], $result);
    }
}
