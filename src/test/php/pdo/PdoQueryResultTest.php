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

use function bovigo\assert\{
    assertThat,
    assertEmptyArray,
    assertFalse,
    assertTrue,
    expect,
    predicate\contains,
    predicate\equals,
    predicate\isInstanceOf,
    predicate\isSameAs
};
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
/**
 * Test for stubbles\db\pdo\PdoQueryResult.
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 */
class PdoQueryResultTest extends TestCase
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

    protected function setUp(): void
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
        $this->basePdoStatement->returns(['bindColumn' => true]);
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
        $this->basePdoStatement->returns(
                ['bindColumn' => throws(new \PDOException('error'))]
        );
        expect(function() use($bar) {
                $this->pdoQueryResult->bindColumn('foo', $bar, \PDO::PARAM_INT);
        })->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithoutArguments()
    {
        $this->basePdoStatement->returns(['fetch' => true]);
        assertTrue($this->pdoQueryResult->fetch());
        verify($this->basePdoStatement, 'fetch')
            ->received(\PDO::FETCH_ASSOC, isSameAs(\PDO::FETCH_ORI_NEXT), isSameAs(0));
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchAssoc()
    {
        $this->basePdoStatement->returns(['fetch' => false]);
        assertFalse(
            $this->pdoQueryResult->fetch(
                \PDO::FETCH_ASSOC,
                ['cursorOrientation' => \PDO::FETCH_ORI_FIRST]
            )
        );
        verify($this->basePdoStatement, 'fetch')
            ->received(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_FIRST, isSameAs(0));
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchObj()
    {
        $this->basePdoStatement->returns(['fetch' => []]);
        assertEmptyArray($this->pdoQueryResult->fetch(
            \PDO::FETCH_OBJ,
            ['cursorOffset' => 50]
        ));
        verify($this->basePdoStatement, 'fetch')
            ->received(\PDO::FETCH_OBJ, isSameAs(\PDO::FETCH_ORI_NEXT), 50);
    }

    /**
     * @test
     */
    public function fetchPassesValuesCorrectlyWithFetchBoth()
    {
        $this->basePdoStatement->returns(['fetch' => 50]);
        assertThat(
            $this->pdoQueryResult->fetch(
                \PDO::FETCH_BOTH,
                ['cursorOrientation' => \PDO::FETCH_ORI_FIRST,
                  'cursorOffset'      => 50,
                  'foo'               => 'bar'
                ]
            ),
            equals(50)
        );
        verify($this->basePdoStatement, 'fetch')
            ->received(\PDO::FETCH_BOTH, \PDO::FETCH_ORI_FIRST, 50);
    }

    /**
     * @test
     */
    public function failingFetchThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
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
        $this->basePdoStatement->returns(['fetchColumn' => true]);
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
        $this->basePdoStatement->returns(
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
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray($this->pdoQueryResult->fetchAll());
    }

    /**
     * @test
     * @group  bug248
     */
    public function fetchAllWithFetchColumnUsesColumnZeroIsDefault()
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
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
        $this->basePdoStatement->returns(['fetchAll' => []]);
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
        $this->basePdoStatement->returns(['fetchAll' => []]);
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
        $this->basePdoStatement->returns(['fetchAll' => []]);
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
        $this->basePdoStatement->returns(['fetchAll' => []]);
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
        $this->basePdoStatement->returns(['fetchAll' => []]);
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
        $this->basePdoStatement->returns(
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
        $this->basePdoStatement->returns(['nextRowset' => true]);
        assertTrue($this->pdoQueryResult->next());
    }

    /**
     * @test
     */
    public function failingNextThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
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
        $this->basePdoStatement->returns(['rowCount' => 5]);
        assertThat($this->pdoQueryResult->count(), equals(5));
    }

    /**
     * @test
     */
    public function failingRowCountThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
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
        $this->basePdoStatement->returns(['closeCursor' => true]);
        assertTrue($this->pdoQueryResult->free());
    }

    /**
     * @test
     */
    public function failingFreeThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
                ['closeCursor' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->free(); })
                ->throws(DatabaseException::class);
    }
}
