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
use stubbles\lang;
/**
 * Test for stubbles\db\DatabaseConnections.
 *
 * @group  db
 * @group  ioc
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
     *
     * @type  \stubbles\db\config\ArrayBasedDatabaseConfigurations
     */
    private $databaseConfigurations;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->databaseConfigurations = new ArrayBasedDatabaseConfigurations(
                                            ['foo'                             => new DatabaseConfiguration('foo', 'dsn:bar'),
                                             DatabaseConfiguration::DEFAULT_ID => new DatabaseConfiguration('default', 'dsn:baz')
                                            ]
        );
        $this->databaseConnections    = new DatabaseConnections($this->databaseConfigurations);
    }

    /**
     * @test
     */
    public function isProviderForDatabaseConnection()
    {
        $this->assertEquals(
                get_class($this->databaseConnections),
                lang\reflect('stubbles\db\DatabaseConnection')
                    ->annotation('ProvidedBy')
                    ->__value()
                    ->getName()
        );
    }

    /**
     * @test
     */
    public function returnsConnectionForRequestedDatabase()
    {
        $this->assertEquals('dsn:bar', $this->databaseConnections->get('foo')->dsn());
    }

    /**
     * @test
     */
    public function usesDefaultConnectionWhenNoNameGiven()
    {
        $this->assertEquals('dsn:baz', $this->databaseConnections->get()->dsn());
    }

    /**
     * @test
     */
    public function returnsSameInstanceWhenSameNameIsRequestedTwice()
    {
        $this->assertSame(
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

        $this->assertEquals(['dsn:bar', 'dsn:baz'], $result);
    }
}