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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\db\config\DatabaseConfiguration;
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
        assertEquals('dsn:bar', $this->pdoConnection->dsn());
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function detailsReturnsDetailsFromConfiguration()
    {
        $this->dbConfig->setDetails('some interesting details about the db');
        assertEquals(
                'some interesting details about the db',
                $this->pdoConnection->details()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function propertyReturnsPropertyFromConfiguration()
    {
        assertEquals('bar', $this->pdoConnection->property('baz'));
    }

    /**
     * assert that a call to an undefined pdo method throws a MethodInvocationException
     *
     * @test
     * @expectedException  BadMethodCallException
     * @expectedExceptionMessage  Call to undefined method stubbles\db\pdo\PdoDatabaseConnection::foo()
     */
    public function undefinedMethod()
    {
        $this->pdoConnection->foo('bar');
    }

    /**
     * @test
     */
    public function connectWithoutInitialQuery()
    {
        $this->pdoConnection->connect();
        callmap\verify($this->pdo, 'query')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function connectExecutesInitialQuery()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->mapCalls(['query' => true]);
        $this->pdoConnection->connect();
        callmap\verify($this->pdo, 'query')->received('set names utf8');
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
        callmap\verify($this->pdo, 'query')->wasCalledOnce();
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
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
        $this->pdoConnection->connect();
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
                     assertEquals(66, $pdoConnection->exec('foo'));
                 }
                ],
                ['lastInsertId',
                 5,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $pdoConnection->connect(); // must be connected
                     assertEquals(5, $pdoConnection->getLastInsertId());
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
                [$method => callmap\throws(new \PDOException('error'))]
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function delegatedMethodCallWrapsPdoExceptionToDatabaseException()
    {
        $this->callThrowsException('commit');
        $this->pdoConnection->commit();
    }

    /**
     * @test
     */
    public function prepareDelegatesToPdoInstanceAndReturnsPdoStatement()
    {
        $this->pdo->mapCalls(['prepare' => NewInstance::of('\PdoStatement')]);
        assertInstanceOf(
                PdoStatement::class,
                $this->pdoConnection->prepare('foo')
        );
        callmap\verify($this->pdo, 'prepare')->received('foo', []);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function prepareThrowsDatabaseExceptionWhenStatementCreationFails()
    {
        $this->callThrowsException('prepare');
        $this->pdoConnection->prepare('foo');
    }

    /**
     * @test
     */
    public function queryWithOutFetchMode()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assertInstanceOf(
                PdoQueryResult::class,
                $this->pdoConnection->query('foo')
        );
        callmap\verify($this->pdo, 'query')->received('foo');
    }

    /**
     * @test
     */
    public function queryWithNoSpecialFetchMode()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assertInstanceOf(
                PdoQueryResult::class,
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_ASSOC]
                )
        );
        callmap\verify($this->pdo, 'query')->received('foo', \PDO::FETCH_ASSOC);
    }

    /**
     * @test
     */
    public function queryWithFetchModeColumn()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assertInstanceOf(
                PdoQueryResult::class,
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_COLUMN, 'colNo' => 5]
                )
        );
        callmap\verify($this->pdo, 'query')->received('foo', \PDO::FETCH_COLUMN, 5);
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function queryWithFetchModeColumnButMissingOptionThrowsIllegalArgumentException()
    {
        $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_COLUMN]);
    }

    /**
     * @test
     */
    public function queryWithFetchModeInto()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        $class = new \stdClass();
        assertInstanceOf(
                PdoQueryResult::class,
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_INTO, 'object' => $class]
                )
        );
        callmap\verify($this->pdo, 'query')
                ->received('foo', \PDO::FETCH_INTO, $class);
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function queryWithFetchModeIntoButMissingOptionThrowsIllegalArgumentException()
    {
        $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_INTO]);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClass()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assertInstanceOf(
                PdoQueryResult::class,
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_CLASS, 'classname' => 'MyClass']
                )
        );
        callmap\verify($this->pdo, 'query')
                ->received('foo', \PDO::FETCH_CLASS, 'MyClass', []);
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function queryWithFetchModeClassButMissingOptionThrowsIllegalArgumentException()
    {
        $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_CLASS]);
    }

    /**
     * @test
     */
    public function queryWithFetchModeClassWithCtorArgs()
    {
        $this->pdo->mapCalls(['query' => NewInstance::of('\PDOStatement')]);
        assertInstanceOf(
                PdoQueryResult::class,
                $this->pdoConnection->query(
                        'foo',
                        ['fetchMode' => \PDO::FETCH_CLASS,
                         'classname' => 'MyClass',
                         'ctorargs' => ['foo']
                        ]
                )
        );
        callmap\verify($this->pdo, 'query')
                ->received('foo', \PDO::FETCH_CLASS, 'MyClass', ['foo']);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function queryThrowsDatabaseExceptionOnFailure()
    {
        $this->callThrowsException('query');
        $this->pdoConnection->query('foo');
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function execThrowsDatabaseExceptionOnFailure()
    {
        $this->callThrowsException('exec');
        $this->pdoConnection->exec('foo');
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  Not connected: can not retrieve last insert id
     */
    public function getLastInsertIdThrowsDatabaseExceptionWhenNotConnected()
    {
        $this->pdoConnection->getLastInsertId();
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function getLastInsertIdThrowsDatabaseExceptionWhenPdoCallFails()
    {
        $this->callThrowsException('lastInsertId');
        $this->pdoConnection->connect()->getLastInsertId();
    }
}
