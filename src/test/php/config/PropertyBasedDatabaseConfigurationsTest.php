<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals
};
use function stubbles\reflect\annotationsOf;
use function stubbles\reflect\annotationsOfConstructorParameter;
/**
 * Test for stubbles\db\config\PropertyBasedDatabaseConfigurations.
 */
#[Group('db')]
#[Group('config')]
class PropertyBasedDatabaseConfigurationsTest extends TestCase
{
    private PropertyBasedDatabaseConfigurations $propertyBasedConfigurations;
    private vfsStreamFile $configFile;

    protected function setUp(): void
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

    #[Test]
    public function annotationsPresentOnClass(): void
    {
        assertTrue(
            annotationsOf($this->propertyBasedConfigurations)
                ->contain('Singleton')
        );
    }

    /**
     * @return  array<string[]>
     */
    public static function annotatedParameters(): Generator
    {
        yield ['configPath', 'stubbles.config.path'];
        yield ['descriptor', 'stubbles.db.descriptor'];
        yield ['fallback', 'stubbles.db.fallback'];
    }

    /**
     * @test
     * @dataProvider  annotatedParameters
     */
    #[Test]
    #[DataProvider('annotatedParameters')]
    public function annotationsPresentOnConstructor(
        string $parameterName,
        string $expectedName
    ): void {
        $annotations = annotationsOfConstructorParameter(
            $parameterName,
            $this->propertyBasedConfigurations
        );
        assertTrue($annotations->contain('Named'));
        assertThat(
            $annotations->firstNamed('Named')->getName(),
            equals($expectedName)
        );
    }

    #[Test]
    public function isDefaultImplementationForDatabaseInitializerInterface(): void
    {
        assertThat(
            annotationsOf(DatabaseConfigurations::class)
                ->firstNamed('ImplementedBy')->__value()->getName(),
            equals(get_class($this->propertyBasedConfigurations))
        );
    }

    #[Test]
    public function containsConfigWhenPresentInFile(): void
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assertTrue($this->propertyBasedConfigurations->contain('foo'));
    }

    #[Test]
    public function containsConfigWhenNotPresentInFileButDefaultAndFallbackEnabled(): void
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        assertTrue($this->propertyBasedConfigurations->contain('foo'));
    }

    #[Test]
    public function doesNotContainConfigWhenNotPresentInFileAndNoDefaultAndFallbackEnabled(): void
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        assertFalse($this->propertyBasedConfigurations->contain('foo'));
    }

    #[Test]
    public function doesNotContainConfigWhenNotPresentInFileAndFallbackDisabled(): void
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

    #[Test]
    public function throwsConfigurationExceptionWhenDsnPropertyMissing(): void
    {
        $this->configFile->setContent('[foo]
username="root"');
        expect(function() { $this->propertyBasedConfigurations->get('foo'); })
            ->throws(LogicException::class)
            ->withMessage('Missing dsn property in database configuration with id foo');
    }

    #[Test]
    public function returnsConfigWhenPresentInFile(): void
    {
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assertThat(
            $this->propertyBasedConfigurations->get('foo')->getId(),
            equals('foo')
        );
    }

    #[Test]
    public function returnsDefaultConfigWhenNotPresentInFileButDefaultAndFallbackEnabled(): void
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        assertThat(
            $this->propertyBasedConfigurations->get('foo')->getId(),
            equals('default')
        );
    }

    #[Test]
    public function throwsConfigurationExceptionWhenNotPresentInFileAndNoDefaultAndFallbackEnabled(): void
    {
        $this->configFile->setContent('[bar]
dsn="mysql:host=localhost;dbname=example"');
        expect(function() { $this->propertyBasedConfigurations->get('foo'); })
            ->throws(OutOfBoundsException::class)
            ->withMessage('No database configuration known for database requested with id foo');
    }

    #[Test]
    public function throwsConfigurationExceptionWhenNotPresentInFileAndFallbackDisabled(): void
    {
        $propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations(
            $this->createConfigFolder() ,
            'rdbms',
            false
        );
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"');
        expect(fn() => $propertyBasedConfigurations->get('foo'))
            ->throws(OutOfBoundsException::class)
            ->withMessage('No database configuration known for database requested with id foo');
    }

    #[Test]
    public function usesDifferentFileWhenDescriptorChanged(): void
    {
        $propertyBasedConfigurations = new PropertyBasedDatabaseConfigurations(
            $this->createConfigFolder('rdbms-test.ini'),
            'rdbms-test'
        );
        $this->configFile->setContent('[foo]
dsn="mysql:host=localhost;dbname=example"');
        assertThat(
            $propertyBasedConfigurations->get('foo')->getDsn(),
            equals('mysql:host=localhost;dbname=example')
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function canIterateOverConfigurations(): void
    {
        $this->configFile->setContent('[default]
dsn="mysql:host=localhost;dbname=example"

[other]
dsn="mysql:host=example.com;dbname=other"');
        $result = [];
        foreach ($this->propertyBasedConfigurations as $configuration) {
            $result[] = $configuration->getId();
        }

        assertThat($result, equals(['default', 'other']));
    }
}
