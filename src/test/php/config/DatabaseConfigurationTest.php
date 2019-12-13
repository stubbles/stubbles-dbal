<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;
use PHPUnit\Framework\TestCase;
use stubbles\values\Secret;

use function bovigo\assert\{
    assertThat,
    assertEmptyArray,
    assertEmptyString,
    assertFalse,
    assertNull,
    assertTrue,
    predicate\equals
};
/**
 * Test for stubbles\db\config\DatabaseConfiguration.
 *
 * @group  db
 * @group  config
 */
class DatabaseConfigurationTest extends TestCase
{
    /**
     * instance to test
     *
     * @var  DatabaseConfiguration
     */
    private $dbConfig;

    protected function setUp(): void
    {
        $this->dbConfig = new DatabaseConfiguration('foo', 'dsn:bar');
    }

    /**
     * @test
     */
    public function hasGivenId(): void
    {
        assertThat($this->dbConfig->getId(), equals('foo'));
    }

    /**
     * @test
     */
    public function hasGivenDsn(): void
    {
        assertThat($this->dbConfig->getDsn(), equals('dsn:bar'));
    }

    /**
     * @test
     */
    public function hasNoUserNameByDefault(): void
    {
        assertNull($this->dbConfig->getUserName());
    }

    /**
     * @test
     */
    public function userNameCanBeSet(): void
    {
        assertThat(
                $this->dbConfig->setUserName('mikey')->getUserName(),
                equals('mikey')
        );
    }

    /**
     * @test
     */
    public function hasNoPasswordByDefault(): void
    {
        assertNull($this->dbConfig->getPassword());
    }

    /**
     * @test
     */
    public function passwordCanBeSet(): void
    {
        assertThat(
                $this->dbConfig->setPassword(Secret::create('secret'))->getPassword(),
                equals('secret')
        );
    }

    /**
     * @test
     */
    public function hasNoDriverOptionsByDefault(): void
    {
        assertFalse($this->dbConfig->hasDriverOptions());
        assertEmptyArray($this->dbConfig->getDriverOptions());
    }

    /**
     * @test
     */
    public function driverOptionsCanBeSet(): void
    {
         $this->dbConfig->setDriverOptions(['foo' => 'bar']);
         assertTrue($this->dbConfig->hasDriverOptions());
         assertThat($this->dbConfig->getDriverOptions(), equals(['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function hasNoInitialQueryByDefault(): void
    {
        assertFalse($this->dbConfig->hasInitialQuery());
        assertEmptyString($this->dbConfig->getInitialQuery());
    }

    /**
     * @test
     */
    public function initialQueryCanBeSet(): void
    {
         $this->dbConfig->setInitialQuery('set names utf8');
         assertTrue($this->dbConfig->hasInitialQuery());
         assertThat($this->dbConfig->getInitialQuery(), equals('set names utf8'));
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function hasNoDetailsByDefault(): void
    {
        assertNull($this->dbConfig->getDetails());
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function hasDetailsWhenSet(): void
    {
        assertThat(
                $this->dbConfig->setDetails('some interesting details about the db')
                        ->getDetails(),
                equals('some interesting details about the db')
        );
    }

    /**
     * @test
     */
    public function createFromArrayMinimalProperties(): void
    {
        $dbConfig = DatabaseConfiguration::fromArray('foo', 'dsn:bar', []);
        assertThat($dbConfig->getId(), equals('foo'));
        assertThat($dbConfig->getDSN(), equals('dsn:bar'));
        assertNull($dbConfig->getUserName());
        assertNull($dbConfig->getPassword());
        assertFalse($dbConfig->hasDriverOptions());
        assertEmptyArray($dbConfig->getDriverOptions());
        assertFalse($dbConfig->hasInitialQuery());
        assertEmptyString($dbConfig->getInitialQuery());
        assertNull($this->dbConfig->getDetails());
    }

    /**
     * @test
     */
    public function createFromArrayFullProperties(): void
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
        assertThat($dbConfig->getId(), equals('foo'));
        assertThat($dbConfig->getDSN(), equals('dsn:bar'));
        assertThat($dbConfig->getUserName(), equals('root'));
        assertThat($dbConfig->getPassword(), equals('secret'));
        assertFalse($dbConfig->hasDriverOptions());
        assertEmptyArray($dbConfig->getDriverOptions());
        assertTrue($dbConfig->hasInitialQuery());
        assertThat($dbConfig->getInitialQuery(), equals('SET names utf8'));
        assertThat($dbConfig->getDetails(), equals('some interesting details about the db'));
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsNullIfPropertyNotSet(): void
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
    public function returnsDefaultIfPropertyNotSet(): void
    {
        assertThat(
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', [])
                        ->getProperty('baz', 'bar'),
                equals('bar')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsValueIfPropertySet(): void
    {
        assertThat(
                DatabaseConfiguration::fromArray('foo', 'dsn:bar', ['baz' => 'example'])
                        ->getProperty('baz', 'bar'),
                equals('example')
        );
    }
}
