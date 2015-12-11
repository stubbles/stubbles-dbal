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
use stubbles\lang\Secret;
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
        assertEquals('foo', $this->dbConfig->getId());
    }

    /**
     * @test
     */
    public function hasGivenDsn()
    {
        assertEquals('dsn:bar', $this->dbConfig->getDsn());
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
        assertEquals('mikey', $this->dbConfig->setUserName('mikey')->getUserName());
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
        assertEquals('secret', $this->dbConfig->setPassword(Secret::create('secret'))->getPassword());
    }

    /**
     * @test
     */
    public function hasNoDriverOptionsByDefault()
    {
        assertFalse($this->dbConfig->hasDriverOptions());
        assertEquals([], $this->dbConfig->getDriverOptions());
    }

    /**
     * @test
     */
    public function driverOptionsCanBeSet()
    {
         $this->dbConfig->setDriverOptions(['foo' => 'bar']);
         assertTrue($this->dbConfig->hasDriverOptions());
         assertEquals(['foo' => 'bar'], $this->dbConfig->getDriverOptions());
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
         assertEquals('set names utf8', $this->dbConfig->getInitialQuery());
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
        assertEquals(
                'some interesting details about the db',
                $this->dbConfig->setDetails('some interesting details about the db')
                        ->getDetails()
        );
    }

    /**
     * @test
     */
    public function createFromArrayMinimalProperties()
    {
        $dbConfig = DatabaseConfiguration::fromArray('foo', 'dsn:bar', []);
        assertEquals('foo', $dbConfig->getId());
        assertEquals('dsn:bar', $dbConfig->getDSN());
        assertNull($dbConfig->getUserName());
        assertNull($dbConfig->getPassword());
        assertFalse($dbConfig->hasDriverOptions());
        assertEquals([], $dbConfig->getDriverOptions());
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
        assertEquals('foo', $dbConfig->getId());
        assertEquals('dsn:bar', $dbConfig->getDSN());
        assertEquals('root', $dbConfig->getUserName());
        assertEquals('secret', $dbConfig->getPassword());
        assertFalse($dbConfig->hasDriverOptions());
        assertEquals([], $dbConfig->getDriverOptions());
        assertTrue($dbConfig->hasInitialQuery());
        assertEquals('SET names utf8', $dbConfig->getInitialQuery());
        assertEquals('some interesting details about the db', $dbConfig->getDetails());
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
        assertEquals(
                'bar',
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', [])
                        ->getProperty('baz', 'bar')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsValueIfPropertySet()
    {
        assertEquals(
                'example',
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', ['baz' => 'example'])
                        ->getProperty('baz', 'bar')
        );
    }
}
