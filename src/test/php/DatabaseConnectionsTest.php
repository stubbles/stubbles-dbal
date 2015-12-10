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
use stubbles\lang\reflect;
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
                new ArrayBasedDatabaseConfigurations(
                        ['foo'                             => new DatabaseConfiguration('foo', 'dsn:bar'),
                         DatabaseConfiguration::DEFAULT_ID => new DatabaseConfiguration('default', 'dsn:baz')
                        ]
                )
        );
    }

    /**
     * @test
     */
    public function isProviderForDatabaseConnection()
    {
        assertEquals(
                get_class($this->databaseConnections),
                reflect\annotationsOf(DatabaseConnection::class)
                    ->firstNamed('ProvidedBy')
                    ->__value()
                    ->getName()
        );
    }

    /**
     * @test
     */
    public function returnsConnectionForRequestedDatabase()
    {
        assertEquals('dsn:bar', $this->databaseConnections->get('foo')->dsn());
    }

    /**
     * @test
     */
    public function usesDefaultConnectionWhenNoNameGiven()
    {
        assertEquals('dsn:baz', $this->databaseConnections->get()->dsn());
    }

    /**
     * @test
     */
    public function returnsSameInstanceWhenSameNameIsRequestedTwice()
    {
        assertSame(
                $this->databaseConnections->get('foo'),
                $this->databaseConnections->get('foo')
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

        assertEquals(['dsn:bar', 'dsn:baz'], $result);
    }
}