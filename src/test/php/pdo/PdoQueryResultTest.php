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
     * @type  \stubbles\db\pdo\PdoQueryResult
     */
    private $pdoQueryResult;
    /**
     * mock for pdo
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $basePdoStatement;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->basePdoStatement = NewInstance::of('\PDOStatement');
        $this->pdoQueryResult   = new PdoQueryResult($this->basePdoStatement);
    }

    /**
     * @test
     */
    public function bindColumnPassesValuesCorrectly()
    {
        $bar = 1;
        $this->basePdoStatement->mapCalls(['bindColumn' => true]);
        assertTrue($this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT));
        assertEquals(
                ['foo', $bar, \PDO::PARAM_INT, null, null],
                $this->basePdoStatement->argumentsReceivedFor('bindColumn')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindColumnThrowsDatabaseException()
    {
        $bar = 1;
        $this->basePdoStatement->mapCalls(
                ['bindColumn' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT);
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithoutArguments()
    {
        $this->basePdoStatement->mapCalls(['fetch' => true]);
        assertTrue($this->pdoQueryResult->fetch());
        assertEquals(
                [\PDO::FETCH_ASSOC, null, null],
                $this->basePdoStatement->argumentsReceivedFor('fetch')
        );
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchAssoc()
    {
        $this->basePdoStatement->mapCalls(['fetch' => false]);
        assertFalse(
                $this->pdoQueryResult->fetch(
                        \PDO::FETCH_ASSOC,
                        ['cursorOrientation' => 'foo']
                )
        );
        assertEquals(
                [\PDO::FETCH_ASSOC, 'foo', null],
                $this->basePdoStatement->argumentsReceivedFor('fetch')
        );
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchObj()
    {
        $this->basePdoStatement->mapCalls(['fetch' => []]);
        assertEquals(
                [],
                $this->pdoQueryResult->fetch(\PDO::FETCH_OBJ, ['cursorOffset' => 50])
        );
        assertEquals(
                [\PDO::FETCH_OBJ, null, 50],
                $this->basePdoStatement->argumentsReceivedFor('fetch')
        );
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchBoth()
    {
        $this->basePdoStatement->mapCalls(['fetch' => 50]);
        assertEquals(
                50,
                $this->pdoQueryResult->fetch(
                        \PDO::FETCH_BOTH,
                        ['cursorOrientation' => 'foo',
                         'cursorOffset'      => 50,
                         'foo'               => 'bar'
                        ]
                )
        );
        assertEquals(
                [\PDO::FETCH_BOTH, 'foo', 50],
                $this->basePdoStatement->argumentsReceivedFor('fetch')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetch' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->fetch();
    }

    /**
     * @test
     */
    public function fetchOnePassesValuesCorrectly()
    {
        $this->basePdoStatement->mapCalls(['fetchColumn' => true]);
        assertTrue($this->pdoQueryResult->fetchOne());
        assertTrue($this->pdoQueryResult->fetchOne(5));
        assertEquals(
                [5],
                $this->basePdoStatement->argumentsReceivedFor('fetchColumn', 2)
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchOneThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetchColumn' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->fetchOne();
    }

    /**
     * @test
     */
    public function fetchAllWithoutArguments()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals([], $this->pdoQueryResult->fetchAll());
    }

    /**
     * @test
     * @group  bug248
     */
    public function fetchAllWithFetchColumnUsesColumnZeroIsDefault()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals([], $this->pdoQueryResult->fetchAll(\PDO::FETCH_COLUMN));
        assertEquals(
                [\PDO::FETCH_COLUMN, 0],
                $this->basePdoStatement->argumentsReceivedFor('fetchAll')
        );
    }

    /**
     * @test
     * @group  bug248
     */
    public function fetchAllWithFetchColumnUsesGivenColumn()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_COLUMN,
                        ['columnIndex' => 2]
                )
        );
        assertEquals(
                [\PDO::FETCH_COLUMN, 2],
                $this->basePdoStatement->argumentsReceivedFor('fetchAll')
        );
    }

    /**
     * @test
     */
    public function fetchAllWithFetchObject()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals([], $this->pdoQueryResult->fetchAll(\PDO::FETCH_OBJ));
        assertEquals(
                [\PDO::FETCH_OBJ],
                $this->basePdoStatement->argumentsReceivedFor('fetchAll')
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithoutClassThrowsIllegalArgumentException()
    {
        $this->pdoQueryResult->fetchAll(\PDO::FETCH_CLASS);
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithoutArguments()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_CLASS,
                        ['classname' => 'ExampleClass']
                )
        );
        assertEquals(
                [\PDO::FETCH_CLASS, 'ExampleClass', null],
                $this->basePdoStatement->argumentsReceivedFor('fetchAll')
        );
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithArguments()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_CLASS,
                        ['classname' => 'ExampleClass', 'arguments' => 'foo']
                )
        );
        assertEquals(
                [\PDO::FETCH_CLASS, 'ExampleClass', 'foo'],
                $this->basePdoStatement->argumentsReceivedFor('fetchAll')
        );
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchFunc()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals(
                [],
                $this->pdoQueryResult->fetchAll(
                        \PDO::FETCH_FUNC,
                        ['function' => 'exampleFunc']
                )
        );
        assertEquals(
                [\PDO::FETCH_FUNC, 'exampleFunc'],
                $this->basePdoStatement->argumentsReceivedFor('fetchAll')
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
        $this->pdoQueryResult->fetchAll(\PDO::FETCH_FUNC);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchAllThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetchAll' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->fetchAll();
    }

    /**
     * @test
     */
    public function nextPassesValuesCorrectly()
    {
        $this->basePdoStatement->mapCalls(['nextRowset' => true]);
        assertTrue($this->pdoQueryResult->next());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingNextThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['nextRowset' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->next();
    }

    /**
     * @test
     */
    public function rowCountPassesValuesCorrectly()
    {
        $this->basePdoStatement->mapCalls(['rowCount' => 5]);
        assertEquals(5, $this->pdoQueryResult->count());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingRowCountThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['rowCount' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->count();
    }

    /**
     * @test
     */
    public function freeClosesResultCursor()
    {
        $this->basePdoStatement->mapCalls(['closeCursor' => true]);
        assertTrue($this->pdoQueryResult->free());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFreeThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['closeCursor' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->free();
    }
}
