<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\pdo;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PDO;
use PDOException;
use PDOStatement as PhpPdoStatement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('db')]
#[Group('pdo')]
#[RequiresPhpExtension('pdo')]
class PdoQueryResultTest extends TestCase
{
    private PdoQueryResult $pdoQueryResult;
    private PhpPdoStatement&ClassProxy $basePdoStatement;

    protected function setUp(): void
    {
        $this->basePdoStatement = NewInstance::of(PhpPdoStatement::class);
        $this->pdoQueryResult   = new PdoQueryResult($this->basePdoStatement);
    }

    #[Test]
    public function bindColumnPassesWithoutTypeValuesCorrectly(): void
    {
        $bar = 'world';
        $this->basePdoStatement->returns(['bindColumn' => true]);
        assertTrue($this->pdoQueryResult->bindColumn('hello', $bar));
        verify($this->basePdoStatement, 'bindColumn')
            ->received('hello', $bar);
    }

    #[Test]
    public function bindColumnPassesWithTypeValuesCorrectly(): void
    {
        $bar = 1;
        $this->basePdoStatement->returns(['bindColumn' => true]);
        assertTrue($this->pdoQueryResult->bindColumn('foo', $bar, PDO::PARAM_INT));
        verify($this->basePdoStatement, 'bindColumn')
            ->received('foo', $bar, PDO::PARAM_INT);
    }

    #[Test]
    public function failingBindColumnThrowsDatabaseException(): void
    {
        $bar = 1;
        $this->basePdoStatement->returns(
            ['bindColumn' => throws(new PDOException('error'))]
        );
        expect(function() use($bar) {
            $this->pdoQueryResult->bindColumn('foo', $bar, PDO::PARAM_INT);
        })->throws(DatabaseException::class);
    }

    #[Test]
    public function fetchPassesValuesCorrectlyWithoutArguments(): void
    {
        $this->basePdoStatement->returns(['fetch' => true]);
        assertTrue($this->pdoQueryResult->fetch());
        verify($this->basePdoStatement, 'fetch')
            ->received(PDO::FETCH_ASSOC, isSameAs(PDO::FETCH_ORI_NEXT), isSameAs(0));
    }

    #[Test]
    public function fetchPassesValuesCorrectlyWithFetchAssoc(): void
    {
        $this->basePdoStatement->returns(['fetch' => false]);
        assertFalse(
        $this->pdoQueryResult->fetch(
            PDO::FETCH_ASSOC,
            ['cursorOrientation' => PDO::FETCH_ORI_FIRST]
        )
        );
        verify($this->basePdoStatement, 'fetch')
            ->received(PDO::FETCH_ASSOC, PDO::FETCH_ORI_FIRST, isSameAs(0));
    }

    #[Test]
    public function fetchPassesValuesCorrectlyWithFetchObj(): void
    {
        $this->basePdoStatement->returns(['fetch' => []]);
        assertEmptyArray($this->pdoQueryResult->fetch(
            PDO::FETCH_OBJ,
            ['cursorOffset' => 50]
        ));
        verify($this->basePdoStatement, 'fetch')
            ->received(PDO::FETCH_OBJ, isSameAs(PDO::FETCH_ORI_NEXT), 50);
    }

    #[Test]
    public function fetchPassesValuesCorrectlyWithFetchBoth(): void
    {
        $this->basePdoStatement->returns(['fetch' => 50]);
        assertThat(
            $this->pdoQueryResult->fetch(
                PDO::FETCH_BOTH,
                [
                    'cursorOrientation' => PDO::FETCH_ORI_FIRST,
                    'cursorOffset'      => 50,
                    'foo'               => 'bar'
                ]
            ),
            equals(50)
        );
        verify($this->basePdoStatement, 'fetch')
            ->received(PDO::FETCH_BOTH, PDO::FETCH_ORI_FIRST, 50);
    }

    #[Test]
    public function failingFetchThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['fetch' => throws(new PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->fetch(); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function fetchOnePassesValuesCorrectly(): void
    {
        $this->basePdoStatement->returns(['fetchColumn' => true]);
        assertTrue($this->pdoQueryResult->fetchOne());
        assertTrue($this->pdoQueryResult->fetchOne(5));
        verify($this->basePdoStatement, 'fetchColumn')
            ->receivedOn(2, 5);
    }

    #[Test]
    public function failingFetchOneThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['fetchColumn' => throws(new PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->fetchOne(); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function fetchAllWithoutArguments(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray($this->pdoQueryResult->fetchAll());
    }

    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchColumnUsesColumnZeroIsDefault(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray($this->pdoQueryResult->fetchAll(PDO::FETCH_COLUMN));
        verify($this->basePdoStatement, 'fetchAll')
            ->received(PDO::FETCH_COLUMN, 0);
    }

    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchColumnUsesGivenColumn(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray(
            $this->pdoQueryResult->fetchAll(
                PDO::FETCH_COLUMN,
                ['columnIndex' => 2]
            )
        );
        verify($this->basePdoStatement, 'fetchAll')
            ->received(PDO::FETCH_COLUMN, 2);
    }

    #[Test]
    public function fetchAllWithFetchObject(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray($this->pdoQueryResult->fetchAll(PDO::FETCH_OBJ));
        verify($this->basePdoStatement, 'fetchAll')
            ->received(PDO::FETCH_OBJ);
    }

    /**
     * @since  1.3.2
     */
    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchClassWithoutClassThrowsIllegalArgumentException(): void
    {
        expect(function() { $this->pdoQueryResult->fetchAll(PDO::FETCH_CLASS); })
            ->throws(\InvalidArgumentException::class);
    }

    /**
     * @since  1.3.2
     */
    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchClassWithoutArguments(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray(
            $this->pdoQueryResult->fetchAll(
                PDO::FETCH_CLASS,
                ['classname' => 'ExampleClass']
            )
        );
        verify($this->basePdoStatement, 'fetchAll')
            ->received(PDO::FETCH_CLASS, 'ExampleClass', null);
    }

    /**
     * @since  1.3.2
     */
    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchClassWithArguments(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray(
            $this->pdoQueryResult->fetchAll(
                PDO::FETCH_CLASS,
                ['classname' => 'ExampleClass', 'arguments' => 'foo']
            )
        );
        verify($this->basePdoStatement, 'fetchAll')
            ->received(PDO::FETCH_CLASS, 'ExampleClass', 'foo');
    }

    /**
     * @since  1.3.2
     */
    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchFunc(): void
    {
        $this->basePdoStatement->returns(['fetchAll' => []]);
        assertEmptyArray(
            $this->pdoQueryResult->fetchAll(
                PDO::FETCH_FUNC,
                ['function' => 'exampleFunc']
            )
        );
        verify($this->basePdoStatement, 'fetchAll')
            ->received(PDO::FETCH_FUNC, 'exampleFunc');
    }

    /**
     * @since  1.3.2
     */
    #[Test]
    #[Group('bug248')]
    public function fetchAllWithFetchFuncWithMissingFunctionThrowsIllegalArgumentException(): void
    {
        expect(function() { $this->pdoQueryResult->fetchAll(PDO::FETCH_FUNC); })
            ->throws(\InvalidArgumentException::class);
    }

    #[Test]
    public function failingFetchAllThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['fetchAll' => throws(new PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->fetchAll(); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function nextPassesValuesCorrectly(): void
    {
        $this->basePdoStatement->returns(['nextRowset' => true]);
        assertTrue($this->pdoQueryResult->next());
    }

    #[Test]
    public function failingNextThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['nextRowset' => throws(new PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->next(); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function rowCountPassesValuesCorrectly(): void
    {
        $this->basePdoStatement->returns(['rowCount' => 5]);
        assertThat($this->pdoQueryResult->count(), equals(5));
    }

    #[Test]
    public function failingRowCountThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['rowCount' => throws(new PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->count(); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function freeClosesResultCursor(): void
    {
        $this->basePdoStatement->returns(['closeCursor' => true]);
        assertTrue($this->pdoQueryResult->free());
    }

    #[Test]
    public function failingFreeThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['closeCursor' => throws(new PDOException('error'))]
        );
        expect(function() { $this->pdoQueryResult->free(); })
            ->throws(DatabaseException::class);
    }
}
