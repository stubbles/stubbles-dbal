<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\db\config\ArrayBasedDatabaseConfigurations;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
use function stubbles\reflect\annotationsOf;
/**
 * Test for stubbles\db\DatabaseConnections.
 */
#[Group('db')]
class DatabaseConnectionsTest extends TestCase
{
    private DatabaseConnections $databaseConnections;

    protected function setUp(): void
    {
        $this->databaseConnections = new DatabaseConnections(
            new ArrayBasedDatabaseConfigurations([
                'foo'                             => new DatabaseConfiguration('foo', 'dsn:bar'),
                DatabaseConfiguration::DEFAULT_ID => new DatabaseConfiguration('default', 'dsn:baz')
            ])
        );
    }

    #[Test]
    public function isProviderForDatabaseConnection(): void
    {
        assertThat(
            annotationsOf(DatabaseConnection::class)
                ->firstNamed('ProvidedBy')
                ->__value()
                ->getName(),
            equals(get_class($this->databaseConnections))
        );
    }

    #[Test]
    public function returnsConnectionForRequestedDatabase(): void
    {
        assertThat(
            $this->databaseConnections->get('foo')->dsn(),
            equals('dsn:bar')
        );
    }

    #[Test]
    public function usesDefaultConnectionWhenNoNameGiven(): void
    {
        assertThat($this->databaseConnections->get()->dsn(), equals('dsn:baz'));
    }

    #[Test]
    public function returnsSameInstanceWhenSameNameIsRequestedTwice(): void
    {
        assertThat(
            $this->databaseConnections->get('foo'),
            isSameAs($this->databaseConnections->get('foo'))
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function canIterateOverAvailableConnections(): void
    {
        $result = [];
        foreach ($this->databaseConnections as $connection) {
            $result[] = $connection->dsn();
        }

        assertThat($result, equals(['dsn:bar', 'dsn:baz']));
    }
}
