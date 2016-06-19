<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db;
use stubbles\db\config\ArrayBasedDatabaseConfigurations;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
use function stubbles\reflect\annotationsOf;
/**
 * Test for stubbles\db\DatabaseConnections.
 *
 * @group  db
 */
class DatabaseConnectionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\db\DatabaseConnections
     */
    private $databaseConnections;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->databaseConnections = new DatabaseConnections(
                new ArrayBasedDatabaseConfigurations([
                        'foo'                             => new DatabaseConfiguration('foo', 'dsn:bar'),
                        DatabaseConfiguration::DEFAULT_ID => new DatabaseConfiguration('default', 'dsn:baz')
                ])
        );
    }

    /**
     * @test
     */
    public function isProviderForDatabaseConnection()
    {
        assert(
                annotationsOf(DatabaseConnection::class)
                    ->firstNamed('ProvidedBy')
                    ->__value()
                    ->getName(),
                equals(get_class($this->databaseConnections))
        );
    }

    /**
     * @test
     */
    public function returnsConnectionForRequestedDatabase()
    {
        assert(
                $this->databaseConnections->get('foo')->dsn(),
                equals('dsn:bar')
        );
    }

    /**
     * @test
     */
    public function usesDefaultConnectionWhenNoNameGiven()
    {
        assert($this->databaseConnections->get()->dsn(), equals('dsn:baz'));
    }

    /**
     * @test
     */
    public function returnsSameInstanceWhenSameNameIsRequestedTwice()
    {
        assert(
                $this->databaseConnections->get('foo'),
                isSameAs($this->databaseConnections->get('foo'))
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function canIterateOverAvailableConnections()
    {
        $result = [];
        foreach ($this->databaseConnections as $connection) {
            $result[] = $connection->dsn();
        }

        assert($result, equals(['dsn:bar', 'dsn:baz']));
    }
}
