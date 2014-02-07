<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\config;
/**
 * Test for net\stubbles\db\config\DatabaseConfiguration.
 *
 * @group  db
 * @group  config
 */
class DatabaseConfigurationTestCase extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals('foo', $this->dbConfig->getId());
    }

    /**
     * @test
     */
    public function hasGivenDsn()
    {
        $this->assertEquals('dsn:bar', $this->dbConfig->getDsn());
    }

    /**
     * @test
     */
    public function hasNoUserNameByDefault()
    {
        $this->assertNull($this->dbConfig->getUserName());
    }

    /**
     * @test
     */
    public function userNameCanBeSet()
    {
        $this->assertEquals('mikey', $this->dbConfig->setUserName('mikey')->getUserName());
    }

    /**
     * @test
     */
    public function hasNoPasswordByDefault()
    {
        $this->assertNull($this->dbConfig->getPassword());
    }

    /**
     * @test
     */
    public function passwordCanBeSet()
    {
        $this->assertEquals('secret', $this->dbConfig->setPassword('secret')->getPassword());
    }

    /**
     * @test
     */
    public function hasNoDriverOptionsByDefault()
    {
        $this->assertFalse($this->dbConfig->hasDriverOptions());
        $this->assertEquals(array(), $this->dbConfig->getDriverOptions());
    }

    /**
     * @test
     */
    public function driverOptionsCanBeSet()
    {
         $this->dbConfig->setDriverOptions(array('foo' => 'bar'));
         $this->assertTrue($this->dbConfig->hasDriverOptions());
         $this->assertEquals(array('foo' => 'bar'), $this->dbConfig->getDriverOptions());
    }

    /**
     * @test
     */
    public function hasNoInitialQueryByDefault()
    {
        $this->assertFalse($this->dbConfig->hasInitialQuery());
        $this->assertNull($this->dbConfig->getInitialQuery());
    }

    /**
     * @test
     */
    public function initialQueryCanBeSet()
    {
         $this->dbConfig->setInitialQuery('set names utf8');
         $this->assertTrue($this->dbConfig->hasInitialQuery());
         $this->assertEquals('set names utf8', $this->dbConfig->getInitialQuery());
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function hasNoDetailsByDefault()
    {
        $this->assertNull($this->dbConfig->getDetails());
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function hasDetailsWhenSet()
    {
        $this->assertEquals('some interesting details about the db',
                            $this->dbConfig->setDetails('some interesting details about the db')
                                           ->getDetails()
        );
    }

    /**
     * @test
     */
    public function createFromArrayMinimalProperties()
    {
        $dbConfig = DatabaseConfiguration::fromArray('foo', 'dsn:bar', array());
        $this->assertEquals('foo', $dbConfig->getId());
        $this->assertEquals('dsn:bar', $dbConfig->getDSN());
        $this->assertNull($dbConfig->getUserName());
        $this->assertNull($dbConfig->getPassword());
        $this->assertFalse($dbConfig->hasDriverOptions());
        $this->assertEquals(array(), $dbConfig->getDriverOptions());
        $this->assertFalse($dbConfig->hasInitialQuery());
        $this->assertNull($dbConfig->getInitialQuery());
        $this->assertNull($this->dbConfig->getDetails());
    }

    /**
     * @test
     */
    public function createFromArrayFullProperties()
    {
        $dbConfig = DatabaseConfiguration::fromArray('foo',
                                                     'dsn:bar',
                                                     array('username'      => 'root',
                                                           'password'      => 'secret',
                                                           'initialQuery'  => 'SET names utf8',
                                                           'details'       => 'some interesting details about the db'
                                                     )
                    );
        $this->assertEquals('foo', $dbConfig->getId());
        $this->assertEquals('dsn:bar', $dbConfig->getDSN());
        $this->assertEquals('root', $dbConfig->getUserName());
        $this->assertEquals('secret', $dbConfig->getPassword());
        $this->assertFalse($dbConfig->hasDriverOptions());
        $this->assertEquals(array(), $dbConfig->getDriverOptions());
        $this->assertTrue($dbConfig->hasInitialQuery());
        $this->assertEquals('SET names utf8', $dbConfig->getInitialQuery());
        $this->assertEquals('some interesting details about the db', $dbConfig->getDetails());
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsNullIfPropertyNotSet()
    {
        $this->assertNull(DatabaseConfiguration::fromArray('foo', 'dsn:bar', array())->getProperty('baz'));
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsDefaultIfPropertyNotSet()
    {
        $this->assertEquals('bar',
                            DatabaseConfiguration::fromArray('foo', 'dsn:bar', array())->getProperty('baz', 'bar')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsValueIfPropertySet()
    {
        $this->assertEquals('example',
                            DatabaseConfiguration::fromArray('foo', 'dsn:bar', array('baz' => 'example'))->getProperty('baz', 'bar')
        );
    }
}
