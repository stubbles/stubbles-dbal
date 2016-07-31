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
use org\bovigo\vfs\vfsStream;

use function bovigo\assert\{
    assert,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals
};
use function stubbles\reflect\annotationsOf;
use function stubbles\reflect\annotationsOfConstructorParameter;
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

    private function createConfigFolder(string $filename = 'rdbms.ini'): string
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

    public function annotatedParameters(): array
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
    public function annotationsPresentOnConstructor(
            string $parameterName,
            string $expectedName
    ) {
        $annotations = annotationsOfConstructorParameter(
                $parameterName,
                $this->propertyBasedConfigurations
        );
        assertTrue($annotations->contain('Named'));
        assert(
                $annotations->firstNamed('Named')->getName(),
                equals($expectedName)
        );
    }

    /**
     * @test
     */
    public function isDefaultImplementationForDatabaseInitializerInterface()
    {
        assert(
                annotationsOf(DatabaseConfigurations::class)
                        ->firstNamed('ImplementedBy')->__value()->getName(),
                equals(get_class($this->propertyBasedConfigurations))
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
        assertFalse($propertyBasedConfigurations->contain('foo'));
    }

    /**
     * @test
     */
    public function throwsConfigurationExceptionWhenDsnPropertyMissing()
    {
        $this->configFile->setContent('[foo]
username="root"');
        expect(function() { $this->propertyBasedConfigurations->get('foo'); })
                ->throws(\LogicException::class)
                ->withMessage('Missing dsn property in database configuration with id foo');
    }

    /**
     * @test
     */
    public function returnsConfigWhenPresentInFile()
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assert(
                $this->propertyBasedConfigurations->get('foo')->getId(),
                equals('foo')
        );
    }

    /**
     * @test
     */
    public function returnsDefaultConfigWhenNotPresentInFileButDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        assert(
                $this->propertyBasedConfigurations->get('foo')->getId(),
                equals('default')
        );
    }

    /**
     * @test
     */
    public function throwsConfigurationExceptionWhenNotPresentInFileAndNoDefaultAndFallbackEnabled()
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        expect(function() { $this->propertyBasedConfigurations->get('foo'); })
                ->throws(\OutOfBoundsException::class)
                ->withMessage('No database configuration known for database requested with id foo');
    }

    /**
     * @test
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
        expect(function() use($propertyBasedConfigurations) {
                $propertyBasedConfigurations->get('foo');
        })
                ->throws(\OutOfBoundsException::class)
                ->withMessage('No database configuration known for database requested with id foo');
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
        assert(
                $propertyBasedConfigurations->get('foo')->getDsn(),
                equals('mysql:host=localhost;dbname=example')
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

        assert($result, equals(['default', 'other']));
    }
}
