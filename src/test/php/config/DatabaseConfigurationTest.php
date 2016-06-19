<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\config;
use stubbles\values\Secret;

use function bovigo\assert\assert;
use function bovigo\assert\assertEmptyArray;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\db\config\DatabaseConfiguration.
 *
 * @group  db
 * @group  config
 */
class DatabaseConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  DatabaseConfiguration
     */
    private $dbConfig;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->dbConfig = new DatabaseConfiguration('foo', 'dsn:bar');
    }

    /**
     * @test
     */
    public function hasGivenId()
    {
        assert($this->dbConfig->getId(), equals('foo'));
    }

    /**
     * @test
     */
    public function hasGivenDsn()
    {
        assert($this->dbConfig->getDsn(), equals('dsn:bar'));
    }

    /**
     * @test
     */
    public function hasNoUserNameByDefault()
    {
        assertNull($this->dbConfig->getUserName());
    }

    /**
     * @test
     */
    public function userNameCanBeSet()
    {
        assert(
                $this->dbConfig->setUserName('mikey')->getUserName(),
                equals('mikey')
        );
    }

    /**
     * @test
     */
    public function hasNoPasswordByDefault()
    {
        assertNull($this->dbConfig->getPassword());
    }

    /**
     * @test
     */
    public function passwordCanBeSet()
    {
        assert(
                $this->dbConfig->setPassword(Secret::create('secret'))->getPassword(),
                equals('secret')
        );
    }

    /**
     * @test
     */
    public function hasNoDriverOptionsByDefault()
    {
        assertFalse($this->dbConfig->hasDriverOptions());
        assertEmptyArray($this->dbConfig->getDriverOptions());
    }

    /**
     * @test
     */
    public function driverOptionsCanBeSet()
    {
         $this->dbConfig->setDriverOptions(['foo' => 'bar']);
         assertTrue($this->dbConfig->hasDriverOptions());
         assert($this->dbConfig->getDriverOptions(), equals(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function hasNoInitialQueryByDefault()
    {
        assertFalse($this->dbConfig->hasInitialQuery());
        assertNull($this->dbConfig->getInitialQuery());
    }

    /**
     * @test
     */
    public function initialQueryCanBeSet()
    {
         $this->dbConfig->setInitialQuery('set names utf8');
         assertTrue($this->dbConfig->hasInitialQuery());
         assert($this->dbConfig->getInitialQuery(), equals('set names utf8'));
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function hasNoDetailsByDefault()
    {
        assertNull($this->dbConfig->getDetails());
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function hasDetailsWhenSet()
    {
        assert(
                $this->dbConfig->setDetails('some interesting details about the db')
                        ->getDetails(),
                equals('some interesting details about the db')
        );
    }

    /**
     * @test
     */
    public function createFromArrayMinimalProperties()
    {
        $dbConfig = DatabaseConfiguration::fromArray('foo', 'dsn:bar', []);
        assert($dbConfig->getId(), equals('foo'));
        assert($dbConfig->getDSN(), equals('dsn:bar'));
        assertNull($dbConfig->getUserName());
        assertNull($dbConfig->getPassword());
        assertFalse($dbConfig->hasDriverOptions());
        assertEmptyArray($dbConfig->getDriverOptions());
        assertFalse($dbConfig->hasInitialQuery());
        assertNull($dbConfig->getInitialQuery());
        assertNull($this->dbConfig->getDetails());
    }

    /**
     * @test
     */
    public function createFromArrayFullProperties()
    {
        $dbConfig = DatabaseConfiguration::fromArray(
                'foo',
                'dsn:bar',
                ['username'      => 'root',
                 'password'      => 'secret',
                 'initialQuery'  => 'SET names utf8',
                 'details'       => 'some interesting details about the db'
                ]
        );
        assert($dbConfig->getId(), equals('foo'));
        assert($dbConfig->getDSN(), equals('dsn:bar'));
        assert($dbConfig->getUserName(), equals('root'));
        assert($dbConfig->getPassword(), equals('secret'));
        assertFalse($dbConfig->hasDriverOptions());
        assertEmptyArray($dbConfig->getDriverOptions());
        assertTrue($dbConfig->hasInitialQuery());
        assert($dbConfig->getInitialQuery(), equals('SET names utf8'));
        assert($dbConfig->getDetails(), equals('some interesting details about the db'));
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsNullIfPropertyNotSet()
    {
        assertNull(
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', [])
                        ->getProperty('baz')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsDefaultIfPropertyNotSet()
    {
        assert(
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', [])
                        ->getProperty('baz', 'bar'),
                equals('bar')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsValueIfPropertySet()
    {
        assert(
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', ['baz' => 'example'])
                        ->getProperty('baz', 'bar'),
                equals('example')
        );
    }
}
