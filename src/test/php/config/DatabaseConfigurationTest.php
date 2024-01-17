<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\config;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('db')]
#[Group('config')]
class DatabaseConfigurationTest extends TestCase
{
    private DatabaseConfiguration $dbConfig;

    protected function setUp(): void
    {
        $this->dbConfig = new DatabaseConfiguration('foo', 'dsn:bar');
    }

    #[Test]
    public function hasGivenId(): void
    {
        assertThat($this->dbConfig->getId(), equals('foo'));
    }

    #[Test]
    public function hasGivenDsn(): void
    {
        assertThat($this->dbConfig->getDsn(), equals('dsn:bar'));
    }

    #[Test]
    public function hasNoUserNameByDefault(): void
    {
        assertNull($this->dbConfig->getUserName());
    }

    #[Test]
    public function userNameCanBeSet(): void
    {
        assertThat(
            $this->dbConfig->setUserName('mikey')->getUserName(),
            equals('mikey')
        );
    }

    #[Test]
    public function hasNoPasswordByDefault(): void
    {
        assertNull($this->dbConfig->getPassword());
    }

    #[Test]
    public function passwordCanBeSet(): void
    {
        assertThat(
            $this->dbConfig->setPassword(Secret::create('secret'))->getPassword(),
            equals('secret')
        );
    }

    #[Test]
    public function hasNoDriverOptionsByDefault(): void
    {
        assertFalse($this->dbConfig->hasDriverOptions());
        assertEmptyArray($this->dbConfig->getDriverOptions());
    }

    #[Test]
    public function driverOptionsCanBeSet(): void
    {
         $this->dbConfig->setDriverOptions(['foo' => 'bar']);
         assertTrue($this->dbConfig->hasDriverOptions());
         assertThat($this->dbConfig->getDriverOptions(), equals(['foo' => 'bar']));
    }

    #[Test]
    public function hasNoInitialQueryByDefault(): void
    {
        assertFalse($this->dbConfig->hasInitialQuery());
        assertEmptyString($this->dbConfig->getInitialQuery());
    }

    #[Test]
    public function initialQueryCanBeSet(): void
    {
         $this->dbConfig->setInitialQuery('set names utf8');
         assertTrue($this->dbConfig->hasInitialQuery());
         assertThat($this->dbConfig->getInitialQuery(), equals('set names utf8'));
    }

    /**
     * @since  2.1.0
     */
    #[Test]
    public function hasNoDetailsByDefault(): void
    {
        assertNull($this->dbConfig->getDetails());
    }

    /**
     * @since  2.1.0
     */
    #[Test]
    public function hasDetailsWhenSet(): void
    {
        assertThat(
                $this->dbConfig->setDetails('some interesting details about the db')
                        ->getDetails(),
                equals('some interesting details about the db')
        );
    }

    #[Test]
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

    #[Test]
    public function createFromArrayFullProperties(): void
    {
        $dbConfig = DatabaseConfiguration::fromArray(
            'foo',
            'dsn:bar',
            [
                'username'      => 'root',
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
     * @since  2.2.0
     */
    #[Test]
    public function returnsNullIfPropertyNotSet(): void
    {
        assertNull(
            DatabaseConfiguration::fromArray('foo', 'dsn:bar', [])
                ->getProperty('baz')
        );
    }

    /**
     * @since  2.2.0
     */
    #[Test]
    public function returnsDefaultIfPropertyNotSet(): void
    {
        assertThat(
            DatabaseConfiguration::fromArray('foo', 'dsn:bar', [])
                ->getProperty('baz', 'bar'),
            equals('bar')
        );
    }

    /**
     * @since  2.2.0
     */
    #[Test]
    public function returnsValueIfPropertySet(): void
    {
        assertThat(
            DatabaseConfiguration::fromArray('foo', 'dsn:bar', ['baz' => 'example'])
                ->getProperty('baz', 'bar'),
            equals('example')
        );
    }
}
