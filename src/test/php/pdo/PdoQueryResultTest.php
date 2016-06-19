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

use function bovigo\assert\assert;
use function bovigo\assert\assertEmptyArray;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\contains;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
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
     */
    public function failingBindColumnThrowsDatabaseException()
    {
        $bar = 1;
        $this->basePdoStatement->mapCalls(
                ['bindColumn' => throws(new \PDOException('error'))]
        );
        expect(function() {
                $this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT);
        })->throws(DatabaseException::class);
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
        assertEmptyArray($this->pdoQueryResult->fetch(
                \PDO::FETCH_OBJ,
                ['cursorOffset' => 50]
        ));
        verify($this->basePdoStatement, 'fetch')
                ->received(\PDO::FETCH_OBJ, null, 50);
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchBoth()
    {
        $this->basePdoStatement->mapCalls(['fetch' => 50]);
        assert(
                $this->pdoQueryResult->fetch(
                        \PDO::FETCH_BOTH,
                        ['cursorOrientation' => 'foo',
                         'cursorOffset'      => 50,
                         'foo'               => 'bar'
                        ]
                ),
                equals(50)
        );
        verify($this->basePdoStatement, 'fetch')
                ->received(\PDO::FETCH_BOTH, 'foo', 50);
    }

    /**
     * @test
     */
    public function failingFetchThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetch' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->fetch(); })
                ->throws(DatabaseException::class);
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
     */
    public function failingFetchOneThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetchColumn' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->fetchOne(); })
                ->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function fetchAllWithoutArguments()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEmptyArray($this->pdoQueryResult->fetchAll());
    }

    /**
     * @test
     * @group  bug248
     */
    public function fetchAllWithFetchColumnUsesColumnZeroIsDefault()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEmptyArray($this->pdoQueryResult->fetchAll(\PDO::FETCH_COLUMN));
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
        assertEmptyArray(
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
        assertEmptyArray($this->pdoQueryResult->fetchAll(\PDO::FETCH_OBJ));
        verify($this->basePdoStatement, 'fetchAll')
                ->received(\PDO::FETCH_OBJ);
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithoutClassThrowsIllegalArgumentException()
    {
        expect(function() { $this->pdoQueryResult->fetchAll(\PDO::FETCH_CLASS); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchClassWithoutArguments()
    {
        $this->basePdoStatement->mapCalls(['fetchAll' => []]);
        assertEmptyArray(
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
        assertEmptyArray(
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
        assertEmptyArray(
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
     * @since  1.3.2
     * @group  bug248
     */
    public function fetchAllWithFetchFuncWithMissingFunctionThrowsIllegalArgumentException()
    {
        expect(function() { $this->pdoQueryResult->fetchAll(\PDO::FETCH_FUNC); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function failingFetchAllThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['fetchAll' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->fetchAll(); })
                ->throws(DatabaseException::class);
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
     */
    public function failingNextThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['nextRowset' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->next(); })
                ->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function rowCountPassesValuesCorrectly()
    {
        $this->basePdoStatement->mapCalls(['rowCount' => 5]);
        assert($this->pdoQueryResult->count(), equals(5));
    }

    /**
     * @test
     */
    public function failingRowCountThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['rowCount' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->count(); })
                ->throws(DatabaseException::class);
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
     */
    public function failingFreeThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['closeCursor' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->free(); })
                ->throws(DatabaseException::class);
    }
}
