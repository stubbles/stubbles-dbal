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
 * Test for stubbles\db\Databases.
 *
 * @group  db
 */
class DatabasesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\db\Databases
     */
    private $databases;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->databases = new Databases(
                new DatabaseConnections(
                        new ArrayBasedDatabaseConfigurations(
                                ['foo'                             => new DatabaseConfiguration('foo', 'dsn:bar'),
                                 DatabaseConfiguration::DEFAULT_ID => new DatabaseConfiguration('default', 'dsn:baz')
                                ]
                        )
                )
        );
    }

    /**
     * @test
     */
    public function isProviderForDatabases()
    {
        $this->assertEquals(
                get_class($this->databases),
                lang\reflect('stubbles\db\Database')
                    ->annotation('ProvidedBy')
                    ->__value()
                    ->getName()
        );
    }

    /**
     * @test
     */
    public function returnsRequestedDatabase()
    {
        $this->assertEquals('dsn:bar', $this->databases->get('foo')->dsn());
    }

    /**
     * @test
     */
    public function usesDefaultWhenNoNameGiven()
    {
        $this->assertEquals('dsn:baz', $this->databases->get()->dsn());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function canIterateOverAvailableDatabases()
    {
        $result = [];
        foreach ($this->databases as $database) {
            $result[] = $database->dsn();
        }

        $this->assertEquals(['dsn:bar', 'dsn:baz'], $result);
    }
}
