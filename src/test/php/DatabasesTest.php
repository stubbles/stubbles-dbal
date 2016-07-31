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
namespace stubbles\db;
use stubbles\db\config\ArrayBasedDatabaseConfigurations;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function stubbles\reflect\annotationsOf;
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
    public function isProviderForDatabase()
    {
        assert(
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
    public function returnsRequestedDatabase()
    {
        assert($this->databases->get('foo')->dsn(), equals('dsn:bar'));
    }

    /**
     * @test
     */
    public function usesDefaultWhenNoNameGiven()
    {
        assert($this->databases->get()->dsn(), equals('dsn:baz'));
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

        assert($result, equals(['dsn:bar', 'dsn:baz']));
    }
}
