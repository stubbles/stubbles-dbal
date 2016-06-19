<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\pdo;
use bovigo\callmap\NewInstance;
use stubbles\db\DatabaseException;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\assert;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\contains;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
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
class PdoDatabaseConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\db\pdo\PdoDatabaseConnection
     */
    private $pdoConnection;
    /**
     * configuration instance
     *
     * @type  \stubbles\db\config\DatabaseConfiguration
     */
    private $dbConfig;
    /**
     * mock for pdo
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $pdo;

    /**
     * set up test environment
     */
    public function setUp()
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

    /**
     * clear test environment
     */
    public function tearDown()
    {
        $this->pdo = null;
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function dsnReturnsDsnFromConfiguration()
    {
        assert($this->pdoConnection->dsn(), equals('dsn:bar'));
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function detailsReturnsDetailsFromConfiguration()
    {
        $this->dbConfig->setDetails('some interesting details about the db');
        assert(
                $this->pdoConnection->details(),
                equals('some interesting details about the db')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function propertyReturnsPropertyFromConfiguration()
    {
        assert($this->pdoConnection->property('baz'), equals('bar'));
    }

    /**
     * assert that a call to an undefined pdo method throws a MethodInvocationException
     *
     * @test
     */
    public function undefinedMethod()
    {
        expect(function() { $this->pdoConnection->foo('bar'); })
                ->throws(\BadMethodCallException::class)
                ->withMessage('Call to undefined method stubbles\db\pdo\PdoDatabaseConnection::foo()');
    }

    /**
     * @test
     */
    public function connectWithoutInitialQuery()
    {
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function connectExecutesInitialQuery()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->mapCalls(['query' => true]);
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->received('set names utf8');
    }

    /**
     * @test
     */
    public function connectExecutesInitialQueryOnlyOnce()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->mapCalls(['query' => true]);
        $this->pdoConnection->connect();
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function connectThrowsDatabaseExceptionWhenPdoFails()
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
     * data provider for method delegation test
     *
     * @return  array
     */
    public function getMethodCalls()
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
                     assert($pdoConnection->exec('foo'), equals(66));
                 }
                ],
                ['lastInsertId',
                 5,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $pdoConnection->connect(); // must be connected
                     assert($pdoConnection->getLastInsertId(), equals(5));
                 }
                ]
        ];
    }

    /**
     * @test
     * @dataProvider  getMethodCalls
     */
    public function delegatesMethodCallsToPdoInstance($method, $returnValue, \Closure $assertion)
    {
        $this->pdo->mapCalls([$method => $returnValue]);
        $assertion($this->pdoConnection);

    }

    /**
     * @param  string  $method
     */
    private function callThrowsException($method)
    {
        $this->pdo->mapCalls(
                [$method => throws(new \PDOException('error'))]
        );
    }

    /**
     * @test
     */
    public function delegatedMethodCallWrapsPdoExceptionToDatabaseException()
    {
        $this->callThrowsException('commit');
        expect(function() { $this->pdoConnection->commit(); })
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    /**
     * @test
     */
    public function prepareDelegatesToPdoInstanceAndReturnsPdoStatement()
    {
        $this->pdo->mapCalls(['prepare' => NewInstance::of('\PdoStatement')]);
        assert(
                $this->pdoConnection->prepare('foo'),
                isInstanceOf(PdoStatement::class)
        );
        verify($this->pdo, 'prepare')->received('foo', []);
    }

    /**
     * @test
     */
    public function prepareThrowsDatabaseExceptionWhenStatementCreationFails()
    {
        $this->callThrowsException('prepare');
        expect(function() { $this->pdoConnection->prepare('foo'); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @test
     */
    public function queryWithOutFetchMode()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assert(
                $this->pdoConnection->query('foo'),
                isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo');
    }

    /**
     * @test
     */
    public function queryWithNoSpecialFetchMode()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assert(
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
    public function queryWithFetchModeColumn()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assert(
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
    public function queryWithFetchModeColumnButMissingOptionThrowsIllegalArgumentException()
    {
        expect(function() {
                $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_COLUMN]);
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeInto()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        $class = new \stdClass();
        assert(
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
    public function queryWithFetchModeIntoButMissingOptionThrowsIllegalArgumentException()
    {
        expect(function() {
                $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_INTO]);
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClass()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assert(
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
    public function queryWithFetchModeClassButMissingOptionThrowsIllegalArgumentException()
    {
        expect(function() {
                $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_CLASS]);
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClassWithCtorArgs()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assert(
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
    public function queryThrowsDatabaseExceptionOnFailure()
    {
        $this->callThrowsException('query');
        expect(function() { $this->pdoConnection->query('foo'); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @test
     */
    public function execThrowsDatabaseExceptionOnFailure()
    {
        $this->callThrowsException('exec');
        expect(function() { $this->pdoConnection->exec('foo'); })
                ->throws(DatabaseException::class)
                ->message(contains('error'));
    }

    /**
     * @test
     */
    public function getLastInsertIdThrowsDatabaseExceptionWhenNotConnected()
    {
        expect(function() { $this->pdoConnection->getLastInsertId(); })
                ->throws(DatabaseException::class)
                ->withMessage('Not connected: can not retrieve last insert id');
    }

    /**
     * @test
     */
    public function getLastInsertIdThrowsDatabaseExceptionWhenPdoCallFails()
    {
        $this->callThrowsException('lastInsertId');
        expect(function() {
                $this->pdoConnection->connect()->getLastInsertId();
        })
        ->throws(DatabaseException::class)
        ->message(contains('error'));
    }
}
