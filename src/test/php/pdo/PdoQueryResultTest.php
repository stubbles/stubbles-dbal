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
/**
 * Test for stubbles\db\pdo\PdoQueryResult.
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 */
class PdoQueryResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  PdoQueryResult
     */
    private $pdoQueryResult;
    /**
     * mock for pdo
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPdoStatement;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockPdoStatement = $this->getMock('\PDOStatement');
        $this->pdoQueryResult   = new PdoQueryResult($this->mockPdoStatement);
    }

    /**
     * @test
     */
    public function bindColumnPassesValuesCorrectly()
    {
        $bar = 1;
        $this->mockPdoStatement->method('bindColumn')
                ->with(equalTo('foo'), equalTo($bar), equalTo(\PDO::PARAM_INT))
                ->will(onConsecutiveCalls(true, false));
        assertTrue($this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT));
        assertFalse($this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT));
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindColumnThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('bindColumn')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT);
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectly()
    {
        $this->mockPdoStatement->expects($this->at(0))
                               ->method('fetch')
                               ->with($this->equalTo(\PDO::FETCH_ASSOC), $this->equalTo(null), $this->equalTo(null))
                               ->will($this->returnValue(true));
        $this->mockPdoStatement->expects($this->at(1))
                               ->method('fetch')
                               ->with($this->equalTo(\PDO::FETCH_ASSOC), $this->equalTo('foo'), $this->equalTo(null))
                               ->will($this->returnValue(false));
        $this->mockPdoStatement->expects($this->at(2))
                               ->method('fetch')
                               ->with($this->equalTo(\PDO::FETCH_OBJ), $this->equalTo(null), $this->equalTo(50))
                               ->will($this->returnValue([]));
        $this->mockPdoStatement->expects($this->at(3))
                               ->method('fetch')
                               ->with($this->equalTo(\PDO::FETCH_BOTH), $this->equalTo('foo'), $this->equalTo(50))
                               ->will($this->returnValue(50));
        assertTrue($this->pdoQueryResult->fetch());
        assertFalse($this->pdoQueryResult->fetch(\PDO::FETCH_ASSOC, ['cursorOrientation' => 'foo']));
        assertEquals([], $this->pdoQueryResult->fetch(\PDO::FETCH_OBJ, ['cursorOffset' => 50]));
        assertEquals(50, $this->pdoQueryResult->fetch(\PDO::FETCH_BOTH, ['cursorOrientation' => 'foo',
                                                                                'cursorOffset'      => 50,
                                                                                'foo'               => 'bar'
                                                                               ]
                                )
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('fetch')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->fetch();
    }

    /**
     * @test
     */
    public function fetchOnePassesValuesCorrectly()
    {
        $this->mockPdoStatement->expects($this->at(0))
                               ->method('fetchColumn')
                               ->with($this->equalTo(0))
                               ->will($this->returnValue(true));
        $this->mockPdoStatement->expects($this->at(1))
                               ->method('fetchColumn')
                               ->with($this->equalTo(5))
                               ->will($this->returnValue(false));
        assertTrue($this->pdoQueryResult->fetchOne());
        assertFalse($this->pdoQueryResult->fetchOne(5));
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchOneThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('fetchColumn')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->fetchOne();
    }

    /**
     * @test
     */
    public function fetchAllWithoutArguments()
    {
        $this->mockPdoStatement->method('fetchAll')->will(returnValue([]));
        assertEquals([], $this->pdoQueryResult->fetchAll());
    }

    /**
     * @test
     * @group  bug248
     */
    public function fetchAllWithFetchColumnUsesColumnZeroIsDefault()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->with(equalTo(\PDO::FETCH_COLUMN), equalTo(0))
                ->will(returnValue([]));
        assertEquals([], $this->pdoQueryResult->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @test
     * @group  bug248
     */
    public function fetchAllWithFetchColumnUsesGivenColumn()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->with(equalTo(\PDO::FETCH_COLUMN), equalTo(2))
                ->will(returnValue([]));
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_COLUMN,
                        ['columnIndex' => 2]
                )
        );
    }

    /**
     * @test
     */
    public function fetchAllWithFetchObject()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->with(equalTo(\PDO::FETCH_OBJ))
                ->will(returnValue([]));
        assertEquals([], $this->pdoQueryResult->fetchAll(\PDO::FETCH_OBJ));
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithoutClassThrowsIllegalArgumentException()
    {
        $this->mockPdoStatement->expects(never())->method('fetchAll');
        $this->pdoQueryResult->fetchAll(\PDO::FETCH_CLASS);
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithoutArguments()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->with(
                        equalTo(\PDO::FETCH_CLASS),
                        equalTo('ExampleClass'),
                        equalTo(null)
                  )->will(returnValue([]));
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_CLASS,
                        ['classname' => 'ExampleClass']
                )
        );
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithArguments()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->with(
                        equalTo(\PDO::FETCH_CLASS),
                        equalTo('ExampleClass'),
                        equalTo('foo')
                  )->will(returnValue([]));
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_CLASS,
                        ['classname' => 'ExampleClass', 'arguments' => 'foo']
                )
        );
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchFunc()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->with(equalTo(\PDO::FETCH_FUNC), equalTo('exampleFunc'))
                ->will($this->returnValue([]));
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_FUNC,
                        ['function' => 'exampleFunc']
                )
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchFuncWithMissingFunctionThrowsIllegalArgumentException()
    {
        $this->mockPdoStatement->expects(never())->method('fetchAll');
        $this->pdoQueryResult->fetchAll(\PDO::FETCH_FUNC);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchAllThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('fetchAll')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->fetchAll();
    }

    /**
     * @test
     */
    public function nextPassesValuesCorrectly()
    {
        $this->mockPdoStatement->method('nextRowset')->will(returnValue(true));
        assertTrue($this->pdoQueryResult->next());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingNextThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('nextRowset')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->next();
    }

    /**
     * @test
     */
    public function rowCountPassesValuesCorrectly()
    {
        $this->mockPdoStatement->method('rowCount')->will(returnValue(5));
        assertEquals(5, $this->pdoQueryResult->count());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingRowCountThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('rowCount')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->count();
    }

    /**
     * @test
     */
    public function freeClosesResultCursor()
    {
        $this->mockPdoStatement->method('closeCursor')->will(returnValue(true));
        assertTrue($this->pdoQueryResult->free());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFreeThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('closeCursor')
                ->will(throwException(new \PDOException('error')));
        $this->pdoQueryResult->free();
    }
}
