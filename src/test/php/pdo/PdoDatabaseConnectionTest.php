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
use stubbles\db\config\DatabaseConfiguration;
/**
 * Helper class for the test.
 */
class TestPDO extends \PDO
{
    public function __construct($dsn, $username, $passwd, $options) {}
}
/**
 * Test for stubbles\db\pdo\PdoDatabaseConnection.
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 */
class PdoDatabaseConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  PdoDatabaseConnection
     */
    private $pdoConnection;
    /**
     * configuration instance
     *
     * @type  DatabaseConfiguration
     */
    private $dbConfig;
    /**
     * mock for pdo
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPdo;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockPdo = $this->getMock('stubbles\db\pdo\TestPDO', [], ['', '', '', []]);
        $this->dbConfig = DatabaseConfiguration::fromArray(
                'foo',
                'dsn:bar',
                ['baz' => 'bar']
        );
        $this->pdoConnection = new PdoDatabaseConnection(
                $this->dbConfig,
                function()
                {
                    return $this->mockPdo;
                }
        );
    }

    /**
     * clear test environment
     */
    public function tearDown()
    {
        $this->pdoConnection->disconnect();
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
        $this->mockPdo->expects(never())->method('query');
        $this->pdoConnection->connect();
    }

    /**
     * @test
     */
    public function connectExecutesInitialQuery()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->mockPdo->expects(once())
                ->method('query')
                ->with(equalTo('set names utf8'));
        $this->pdoConnection->connect();
    }

    /**
     * @test
     */
    public function connectExecutesInitialQueryOnlyOnce()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->mockPdo->expects(once())
                ->method('query')
                ->with(equalTo('set names utf8'));
        $this->pdoConnection->connect();
        $this->pdoConnection->connect();
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
        $this->mockPdo->method($method)->will(returnValue($returnValue));
        $assertion($this->pdoConnection);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function delegatedMethodCallWrapsPdoExceptionToDatabaseException()
    {
        $this->mockPdo->method('commit')
                ->will(throwException(new \PDOException('error')));
        $this->pdoConnection->commit();
    }

    /**
     * @test
     */
    public function prepareDelegatesToPdoInstanceAndReturnsPdoStatement()
    {
        $this->mockPdo->method('prepare')
                ->with(equalTo('foo'), equalTo([]))
                ->will(returnValue($this->getMock('\PDOStatement')));
        assertInstanceOf(
                'stubbles\db\pdo\PdoStatement',
                $this->pdoConnection->prepare('foo')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function prepareThrowsDatabaseExceptionWhenStatementCreationFails()
    {
        $this->mockPdo->method('prepare')
                ->with(equalTo('foo'), equalTo([]))
                ->will(throwException(new \PDOException('error')));
        $this->pdoConnection->prepare('foo');
    }

    /**
     * @test
     */
    public function queryWithOutFetchMode()
    {
        $this->mockPdo->method('query')
                ->with(equalTo('foo'))
                ->will(returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo');
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
    }

    /**
     * @test
     */
    public function queryWithNoSpecialFetchMode()
    {
        $this->mockPdo->method('query')
                ->with(equalTo('foo'), equalTo(\PDO::FETCH_ASSOC))
                ->will(returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query(
                'foo',
                ['fetchMode' => \PDO::FETCH_ASSOC]
        );
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
    }

    /**
     * @test
     */
    public function queryWithFetchModeColumn()
    {
        $this->mockPdo->method('query')
                ->with(equalTo('foo'), equalTo(\PDO::FETCH_COLUMN), equalTo(5))
                ->will(returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query(
                'foo',
                ['fetchMode' => \PDO::FETCH_COLUMN, 'colNo' => 5]
        );
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
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
        $class = new \stdClass();
        $this->mockPdo->method('query')
                ->with(equalTo('foo'), equalTo(\PDO::FETCH_INTO), equalTo($class))
                ->will(returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query(
                'foo',
                ['fetchMode' => \PDO::FETCH_INTO, 'object' => $class]
        );
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
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
        $this->mockPdo->method('query')
                ->with(
                        equalTo('foo'),
                        equalTo(\PDO::FETCH_CLASS),
                        equalTo('MyClass'),
                        equalTo([])
                )->will(returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query(
                'foo',
                ['fetchMode' => \PDO::FETCH_CLASS, 'classname' => 'MyClass']
        );
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
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
        $this->mockPdo->method('query')
                ->with(
                        equalTo('foo'),
                        equalTo(\PDO::FETCH_CLASS),
                        equalTo('MyClass'),
                        equalTo(['foo'])
                )->will(returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query(
                'foo',
                ['fetchMode' => \PDO::FETCH_CLASS,
                 'classname' => 'MyClass',
                 'ctorargs' => ['foo']
                ]
        );
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function queryThrowsDatabaseExceptionOnFailure()
    {
        $this->mockPdo->method('query')
                ->will(throwException(new \PDOException('error')));
        $this->pdoConnection->query('foo');
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function execThrowsDatabaseExceptionOnFailure()
    {
        $this->mockPdo->method('exec')
                ->will(throwException(new \PDOException('error')));
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
        $this->mockPdo->method('lastInsertId')
                ->will(throwException(new \PDOException('error')));
        $this->pdoConnection->connect()->getLastInsertId();
    }
}
