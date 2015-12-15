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

use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
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
     * @type  \bovigo\callmap\Proxy
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
        verify($this->basePdoStatement, 'bindColumn')
                ->received('foo', $bar, \PDO::PARAM_INT, null, null);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindColumnThrowsDatabaseException()
    {
        $bar = 1;
        $this->basePdoStatement->mapCalls(
                ['bindColumn' => throws(new \PDOException('error'))]
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
        verify($this->basePdoStatement, 'fetch')
                ->received(\PDO::FETCH_ASSOC, null, null);
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
        verify($this->basePdoStatement, 'fetch')
                ->received(\PDO::FETCH_ASSOC, 'foo', null);
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
        verify($this->basePdoStatement, 'fetch')
                ->received(\PDO::FETCH_OBJ, null, 50);
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
        verify($this->basePdoStatement, 'fetch')
                ->received(\PDO::FETCH_BOTH, 'foo', 50);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetch' => throws(new \PDOException('error'))]
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
        verify($this->basePdoStatement, 'fetchColumn')
                ->receivedOn(2, 5);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingFetchOneThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetchColumn' => throws(new \PDOException('error'))]
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
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_COLUMN, 0);
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
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_COLUMN, 2);
    }

    /**
     * @test
     */
    public function fetchAllWithFetchObject()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEquals([], $this->pdoQueryResult->fetchAll(\PDO::FETCH_OBJ));
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_OBJ);
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
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_CLASS, 'ExampleClass', null);
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
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_CLASS, 'ExampleClass', 'foo');
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
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_FUNC, 'exampleFunc');
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
                ['fetchAll' => throws(new \PDOException('error'))]
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
                ['nextRowset' => throws(new \PDOException('error'))]
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
                ['rowCount' => throws(new \PDOException('error'))]
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
                ['closeCursor' => throws(new \PDOException('error'))]
        );
        $this->pdoQueryResult->free();
    }
}
