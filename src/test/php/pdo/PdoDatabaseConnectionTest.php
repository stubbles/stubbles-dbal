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
        $mockPdo = $this->mockPdo = $this->getMock('stubbles\db\pdo\TestPDO', [], ['', '', '', []]);
        $this->dbConfig      = DatabaseConfiguration::fromArray('foo', 'dsn:bar', ['baz' => 'bar']);
        $this->pdoConnection = new PdoDatabaseConnection($this->dbConfig,
                                                         function() use ($mockPdo)
                                                         {
                                                             return $mockPdo;
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
        $this->assertEquals('dsn:bar', $this->pdoConnection->dsn());
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function detailsReturnsDetailsFromConfiguration()
    {
        $this->dbConfig->setDetails('some interesting details about the db');
        $this->assertEquals('some interesting details about the db',
                            $this->pdoConnection->details()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function propertyReturnsPropertyFromConfiguration()
    {
        $this->assertEquals('bar',
                            $this->pdoConnection->property('baz')
        );
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
        $this->mockPdo->expects($this->never())
                      ->method('query');
        $this->pdoConnection->connect();
    }

    /**
     * @test
     */
    public function connectExecutesInitialQuery()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('set names utf8'));
        $this->pdoConnection->connect();
    }

    /**
     * @test
     */
    public function connectExecutesInitialQueryOnlyOnce()
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('set names utf8'));
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
        $this->pdoConnection = new PdoDatabaseConnection($this->dbConfig,
                                                         function()
                                                         {
                                                             throw new \PDOException('error');
                                                         }
                               );
        $this->pdoConnection->connect();
    }

    /**
     * data provider for method delegation test
     * @return  array
     */
    public function getMethodCalls()
    {
        $that = $this;
        return [['beginTransaction',
                 true,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $this->assertTrue($pdoConnection->beginTransaction());
                 }
                ],
                ['commit',
                 true,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $this->assertTrue($pdoConnection->commit());
                 }
                ],
                ['rollBack',
                 true,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $this->assertTrue($pdoConnection->rollback());
                 }
                ],
                ['exec',
                 66,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $this->assertEquals(66, $pdoConnection->exec('foo'));
                 }
                ],
                ['lastInsertId',
                 5,
                 function(PdoDatabaseConnection $pdoConnection)
                 {
                     $pdoConnection->connect(); // must be connected
                     $this->assertEquals(5, $pdoConnection->getLastInsertId());
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
        $this->mockPdo->expects($this->once())
                      ->method($method)
                      ->will($this->returnValue($returnValue));
        $assertion($this->pdoConnection);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function delegatedMethodCallWrapsPdoExceptionToDatabaseException()
    {
        $this->mockPdo->expects($this->once())
                      ->method('commit')
                      ->will($this->throwException(new \PDOException('error')));
        $this->pdoConnection->commit();
    }

    /**
     * @test
     */
    public function prepareDelegatesToPdoInstanceAndReturnsPdoStatement()
    {
        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with($this->equalTo('foo'), $this->equalTo([]))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $this->assertInstanceOf('stubbles\db\pdo\PdoStatement',
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
        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with($this->equalTo('foo'), $this->equalTo([]))
                      ->will($this->throwException(new \PDOException('error')));
        $this->pdoConnection->prepare('foo');
    }

    /**
     * @test
     */
    public function queryWithOutFetchMode()
    {
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('foo'))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo');
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
    }

    /**
     * @test
     */
    public function queryWithNoSpecialFetchMode()
    {
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('foo'), $this->equalTo(\PDO::FETCH_ASSOC))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_ASSOC]);
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
    }

    /**
     * @test
     */
    public function queryWithFetchModeColumn()
    {
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('foo'), $this->equalTo(\PDO::FETCH_COLUMN), $this->equalTo(5))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_COLUMN, 'colNo' => 5]);
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
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
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('foo'), $this->equalTo(\PDO::FETCH_INTO), $this->equalTo($class))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_INTO, 'object' => $class]);
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
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
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('foo'), $this->equalTo(\PDO::FETCH_CLASS), $this->equalTo('MyClass'), $this->equalTo([]))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_CLASS, 'classname' => 'MyClass']);
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
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
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->with($this->equalTo('foo'), $this->equalTo(\PDO::FETCH_CLASS), $this->equalTo('MyClass'), $this->equalTo(['foo']))
                      ->will($this->returnValue($this->getMock('\PDOStatement')));
        $statement = $this->pdoConnection->query('foo', ['fetchMode' => \PDO::FETCH_CLASS, 'classname' => 'MyClass', 'ctorargs' => ['foo']]);
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $statement);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function queryThrowsDatabaseExceptionOnFailure()
    {
        $this->mockPdo->expects($this->once())
                      ->method('query')
                      ->will($this->throwException(new \PDOException('error')));
        $this->pdoConnection->query('foo');
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     * @expectedExceptionMessage  error
     */
    public function execThrowsDatabaseExceptionOnFailure()
    {
        $this->mockPdo->expects($this->once())
                      ->method('exec')
                      ->will($this->throwException(new \PDOException('error')));
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
        $this->mockPdo->expects($this->once())
                      ->method('lastInsertId')
                      ->will($this->throwException(new \PDOException('error')));
        $this->pdoConnection->connect()
                            ->getLastInsertId();
    }
}
