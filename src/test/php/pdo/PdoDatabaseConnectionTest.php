<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\pdo;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\db\DatabaseException;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\{
    assertThat,
    assertTrue,
    expect,
    predicate\contains,
    predicate\equals,
    predicate\isInstanceOf
};
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
/**
 * Test for stubbles\db\pdo\PdoDatabaseConnection.
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 * @requires  extension pdo_sqlite
 */
class PdoDatabaseConnectionTest extends TestCase
{
    /**
     * instance to test
     *
     * @var  \stubbles\db\pdo\PdoDatabaseConnection
     */
    private $pdoConnection;
    /**
     * configuration instance
     *
     * @var  \stubbles\db\config\DatabaseConfiguration
     */
    private $dbConfig;
    /**
     * mock for pdo
     *
     * @var  (\PDO&\bovigo\callmap\ClassProxy)
     */
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo      = NewInstance::of('\PDO', ['sqlite::memory:']);
        $this->dbConfig = DatabaseConfiguration::fromArray(
                'foo',
                'dsn:bar',
                ['baz' => 'bar']
        );
        $this->pdoConnection = new PdoDatabaseConnection(
                $this->dbConfig,
                function()
                {
                    return $this->pdo;
                }
        );
    }

    protected function tearDown(): void
    {
        unset($this->pdo);
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function dsnReturnsDsnFromConfiguration(): void
    {
        assertThat($this->pdoConnection->dsn(), equals('dsn:bar'));
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function detailsReturnsDetailsFromConfiguration(): void
    {
        $this->dbConfig->setDetails('some interesting details about the db');
        assertThat(
                $this->pdoConnection->details(),
                equals('some interesting details about the db')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function propertyReturnsPropertyFromConfiguration(): void
    {
        assertThat($this->pdoConnection->property('baz'), equals('bar'));
    }

    /**
     * assert that a call to an undefined pdo method throws a MethodInvocationException
     *
     * @test
     */
    public function undefinedMethod(): void
    {
        expect(function() { $this->pdoConnection->foo('bar'); })
                ->throws(\BadMethodCallException::class)
                ->withMessage(
                        'Call to undefined method ' . PdoDatabaseConnection::class
                        . '::foo()'
                );
    }

    /**
     * @test
     */
    public function connectWithoutInitialQuery(): void
    {
        $this->pdoConnection->connect();
        assertTrue(verify($this->pdo, 'query')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function connectExecutesInitialQuery(): void
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->returns(['query' => true]);
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->received('set names utf8');
    }

    /**
     * @test
     */
    public function connectExecutesInitialQueryOnlyOnce(): void
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->returns(['query' => true]);
        $this->pdoConnection->connect();
        $this->pdoConnection->connect();
        assertTrue(verify($this->pdo, 'query')->wasCalledOnce());
    }

    /**
     * @test
     */
    public function connectThrowsDatabaseExceptionWhenPdoFails(): void
    {
        $this->pdoConnection = new PdoDatabaseConnection(
                $this->dbConfig,
                function()
                {
                    throw new \PDOException('error');
                }
        );
        expect(function() { $this->pdoConnection->connect(); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @return  array<mixed[]>
     */
    public function methodCalls(): array
    {
        return [['beginTransaction',
                 true,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     assertTrue($pdoConnection->beginTransaction());
                 }
                ],
                ['commit',
                 true,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     assertTrue($pdoConnection->commit());
                 }
                ],
                ['rollBack',
                 true,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     assertTrue($pdoConnection->rollback());
                 }
                ],
                ['exec',
                 66,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     assertThat($pdoConnection->exec('foo'), equals(66));
                 }
                ],
                ['lastInsertId',
                 '5',
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $pdoConnection->connect(); // must be connected
                     assertThat($pdoConnection->getLastInsertId(), equals(5));
                 }
                ]
        ];
    }

    /**
     * @param  string    $method       method to be called
     * @param  mixed     $returnValue  value to return from method call
     * @param  callable  $assertion    assertion to apply on connection after method call
     * @test
     * @dataProvider  methodCalls
     */
    public function delegatesMethodCallsToPdoInstance(string $method, $returnValue, callable $assertion): void
    {
        $this->pdo->returns([$method => $returnValue]);
        $assertion($this->pdoConnection);

    }

    private function callThrowsException(string $method): void
    {
        $this->pdo->returns([
                $method => throws(new \PDOException('error'))
        ]);
    }

    /**
     * @test
     */
    public function delegatedMethodCallWrapsPdoExceptionToDatabaseException(): void
    {
        $this->callThrowsException('commit');
        expect(function() { $this->pdoConnection->commit(); })
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    /**
     * @test
     */
    public function prepareDelegatesToPdoInstanceAndReturnsPdoStatement(): void
    {
        $this->pdo->returns(['prepare' => NewInstance::of('\PdoStatement')]);
        assertThat(
                $this->pdoConnection->prepare('foo'),
                isInstanceOf(PdoStatement::class)
        );
        verify($this->pdo, 'prepare')->received('foo', []);
    }

    /**
     * @test
     */
    public function prepareThrowsDatabaseExceptionWhenStatementCreationFails(): void
    {
        $this->callThrowsException('prepare');
        expect(function() { $this->pdoConnection->prepare('foo'); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @test
     */
    public function queryWithOutFetchMode(): void
    {
        $this->pdo->returns(['query' => NewInstance::of('\PDOStatement')]);
        assertThat(
                $this->pdoConnection->query('foo'),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo');
    }

    /**
     * @test
     */
    public function queryWithNoSpecialFetchMode(): void
    {
        $this->pdo->returns(['query' => NewInstance::of('\PDOStatement')]);
        assertThat(
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_ASSOC]
                ),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo', \PDO::FETCH_ASSOC);
    }

    /**
     * @test
     */
    public function queryWithFetchModeColumn(): void
    {
        $this->pdo->returns(['query' => NewInstance::of('\PDOStatement')]);
        assertThat(
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_COLUMN, 'colNo' => 5]
                ),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo', \PDO::FETCH_COLUMN, 5);
    }

    /**
     * @test
     */
    public function queryWithFetchModeColumnButMissingOptionThrowsIllegalArgumentException(): void
    {
        expect(function() {
                $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_COLUMN]);
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeInto(): void
    {
        $this->pdo->returns(['query' => NewInstance::of('\PDOStatement')]);
        $class = new \stdClass();
        assertThat(
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_INTO, 'object' => $class]
                ),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')
                ->received('foo', \PDO::FETCH_INTO, $class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeIntoButMissingOptionThrowsIllegalArgumentException(): void
    {
        expect(function() {
                $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_INTO]);
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClass(): void
    {
        $this->pdo->returns(['query' => NewInstance::of('\PDOStatement')]);
        assertThat(
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_CLASS, 'classname' => 'MyClass']
                ),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')
                ->received('foo', \PDO::FETCH_CLASS, 'MyClass', []);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClassButMissingOptionThrowsIllegalArgumentException(): void
    {
        expect(function() {
                $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_CLASS]);
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClassWithCtorArgs(): void
    {
        $this->pdo->returns(['query' => NewInstance::of('\PDOStatement')]);
        assertThat(
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_CLASS,
                         'classname' => 'MyClass',
                         'ctorargs' => ['foo']
                        ]
                ),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')
                ->received('foo', \PDO::FETCH_CLASS, 'MyClass', ['foo']);
    }

    /**
     * @test
     */
    public function queryThrowsDatabaseExceptionOnFailure(): void
    {
        $this->callThrowsException('query');
        expect(function() { $this->pdoConnection->query('foo'); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @test
     */
    public function execThrowsDatabaseExceptionOnFailure(): void
    {
        $this->callThrowsException('exec');
        expect(function() { $this->pdoConnection->exec('foo'); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @test
     */
    public function getLastInsertIdThrowsDatabaseExceptionWhenNotConnected(): void
    {
        expect(function() { $this->pdoConnection->getLastInsertId(); })
                ->throws(DatabaseException::class)
                ->withMessage('Not connected: can not retrieve last insert id');
    }

    /**
     * @test
     */
    public function getLastInsertIdThrowsDatabaseExceptionWhenPdoCallFails(): void
    {
        $this->callThrowsException('lastInsertId');
        expect(function() {
                $this->pdoConnection->connect()->getLastInsertId();
        })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }
}
