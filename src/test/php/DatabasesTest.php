<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;
use PHPUnit\Framework\TestCase;
use stubbles\db\config\ArrayBasedDatabaseConfigurations;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
use function stubbles\reflect\annotationsOf;
/**
 * Test for stubbles\db\Databases.
 *
 * @group  db
 */
class DatabasesTest extends TestCase
{
    /**
     * instance to test
     *
     * @var  \stubbles\db\Databases
     */
    private $databases;

    protected function setUp(): void
    {
        $this->databases = new Databases(new DatabaseConnections(
                new ArrayBasedDatabaseConfigurations([
                        'foo'                             => new DatabaseConfiguration('foo', 'dsn:bar'),
                        DatabaseConfiguration::DEFAULT_ID => new DatabaseConfiguration('default', 'dsn:baz')
                ])
        ));
    }

    /**
     * @test
     */
    public function isProviderForDatabase(): void
    {
        assertThat(
                annotationsOf(Database::class)
                    ->firstNamed('ProvidedBy')
                    ->__value()
                    ->getName(),
                equals(get_class($this->databases))
        );
    }

    /**
     * @test
     */
    public function returnsRequestedDatabase(): void
    {
        assertThat($this->databases->get('foo')->dsn(), equals('dsn:bar'));
    }

    /**
     * @test
     */
    public function usesDefaultWhenNoNameGiven(): void
    {
        assertThat($this->databases->get()->dsn(), equals('dsn:baz'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function canIterateOverAvailableDatabases(): void
    {
        $result = [];
        foreach ($this->databases as $database) {
            $result[] = $database->dsn();
        }

        assertThat($result, equals(['dsn:bar', 'dsn:baz']));
    }
}
