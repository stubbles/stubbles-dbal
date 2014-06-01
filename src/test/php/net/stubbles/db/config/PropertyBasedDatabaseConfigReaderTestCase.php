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
use org\bovigo\vfs\vfsStream;
use stubbles\lang;
/**
 * Test for net\stubbles\db\config\PropertyBasedDatabaseConfigReader.
 *
 * @group  db
 * @group  config
 */
class PropertyBasedDatabaseConfigReaderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  PropertyBasedDatabaseConfigReader
     */
    private $propertyBasedConfigReader;
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
        $this->propertyBasedConfigReader = new PropertyBasedDatabaseConfigReader($root->url());
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue(
                lang\reflect($this->propertyBasedConfigReader)->hasAnnotation('Singleton')
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $class       = lang\reflect($this->propertyBasedConfigReader);
        $constructor = $class->getConstructor();
        $this->assertTrue($constructor->hasAnnotation('Inject'));
        $this->assertTrue($constructor->hasAnnotation('Named'));
        $this->assertEquals('stubbles.config.path',
                            $constructor->getAnnotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetDescriptorMethod()
    {
        $setDescriptorMethod = lang\reflect($this->propertyBasedConfigReader)->getMethod('setDescriptor');
        $this->assertTrue($setDescriptorMethod->hasAnnotation('Inject'));
        $this->assertTrue($setDescriptorMethod->getAnnotation('Inject')->isOptional());
        $this->assertTrue($setDescriptorMethod->hasAnnotation('Named'));
        $this->assertEquals('stubbles.db.descriptor',
                            $setDescriptorMethod->getAnnotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetFallbackMethod()
    {
        $setFallbackMethod = lang\reflect($this->propertyBasedConfigReader)->getMethod('setFallback');
        $this->assertTrue($setFallbackMethod->hasAnnotation('Inject'));
        $this->assertTrue($setFallbackMethod->getAnnotation('Inject')->isOptional());
        $this->assertTrue($setFallbackMethod->hasAnnotation('Named'));
        $this->assertEquals('stubbles.db.fallback',
                            $setFallbackMethod->getAnnotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function isDefaultImplementationForDatabaseInitializerInterface()
    {
        $refClass = lang\reflect('net\stubbles\db\config\DatabaseConfigReader');
        $this->assertEquals(get_class($this->propertyBasedConfigReader),
                            $refClass->getAnnotation('ImplementedBy')
                                     ->getDefaultImplementation()
                                     ->getName()
        );
    }

    /**
     * @test
     */
    public function hasConfigWhenPresentInFile()
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertTrue($this->propertyBasedConfigReader->hasConfig('foo'));
    }

    /**
     * @test
     */
    public function hasConfigWhenNotPresentInFileButDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertTrue($this->propertyBasedConfigReader->hasConfig('foo'));
    }

    /**
     * @test
     */
    public function doesNothaveConfigWhenNotPresentInFileAndNoDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertFalse($this->propertyBasedConfigReader->hasConfig('foo'));
    }

    /**
     * @test
     */
    public function doesNothaveConfigWhenNotPresentInFileAndFallbackDisabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertFalse($this->propertyBasedConfigReader->setFallback(false)
                                                           ->hasConfig('foo')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\ConfigurationException
     * @expectedExceptionMessage  Missing dsn property in database configuration with id foo
     */
    public function readConfigThrowsConfigurationExceptionWhenDsnPropertyMissing()
    {
        $this->configFile->setContent('[foo]
username="root"');
        $this->propertyBasedConfigReader->readConfig('foo');
    }

    /**
     * @test
     */
    public function returnsConfigWhenPresentInFile()
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertEquals('foo',
                            $this->propertyBasedConfigReader->readConfig('foo')
                                                            ->getId()
        );
    }

    /**
     * @test
     */
    public function returnsDefaultConfigWhenNotPresentInFileButDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertEquals('default',
                            $this->propertyBasedConfigReader->readConfig('foo')
                                                            ->getId()
        );
    }

    /**
     * @test
     */
    public function returnsNullWhenNotPresentInFileAndNoDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertNull($this->propertyBasedConfigReader->readConfig('foo'));
    }

    /**
     * @test
     */
    public function returnsNullWhenNotPresentInFileAndFallbackDisabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        $this->assertNull($this->propertyBasedConfigReader->setFallback(false)
                                                           ->readConfig('foo')
        );
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
                            $this->propertyBasedConfigReader->setDescriptor('rdbms-test')
                                                            ->readConfig('foo')
                                                            ->getDsn()
        );
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function returnsListOfConfigIds()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"

[other]
dsn="mysql:host=example.com;dbname=other"');
        $this->assertEquals(['default', 'other'],
                            $this->propertyBasedConfigReader->configIds()
        );
    }
}
