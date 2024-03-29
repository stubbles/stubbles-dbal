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
use PDOStatement as PhpPdoStatement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\db\DatabaseException;

use function bovigo\assert\{
    assertThat,
    assertTrue,
    expect,
    predicate\contains,
    predicate\equals,
    predicate\isInstanceOf
};
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
/**
 * Test for stubbles\db\pdo\PdoStatement.
 */
#[Group('db')]
#[Group('pdo')]
#[RequiresPhpExtension('pdo')]
class PdoStatementTest extends TestCase
{
    private PdoStatement $pdoStatement;
    private PhpPdoStatement&ClassProxy $basePdoStatement;

    protected function setUp(): void
    {
        $this->basePdoStatement = NewInstance::of(PhpPdoStatement::class);
        $this->pdoStatement     = new PdoStatement($this->basePdoStatement);
    }

    #[Test]
    public function bindParamPassesMinimumValuesCorrectly(): void
    {
        $bar = 'world';
        $this->basePdoStatement->returns(['bindParam' => true]);
        assertTrue($this->pdoStatement->bindParam('hello', $bar));
        verify($this->basePdoStatement, 'bindParam')
            ->received('hello', $bar, \PDO::PARAM_STR);
    }

    #[Test]
    public function bindParamPassesAllValuesCorrectly(): void
    {
        $bar = 1;
        $this->basePdoStatement->returns(['bindParam' => true]);
        assertTrue($this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2));
        verify($this->basePdoStatement, 'bindParam')
            ->received('foo', $bar, \PDO::PARAM_INT, 2, null);
    }

    #[Test]
    public function failingBindParamThrowsDatabaseException(): void
    {
        $bar = 1;
        $this->basePdoStatement->returns(
            ['bindParam' => throws(new \PDOException('error'))]
        );
        expect(function() use($bar) {
            $this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2);
        })->throws(DatabaseException::class);
    }

    #[Test]
    public function bindValuePassesValuesCorrectly(): void
    {
        $this->basePdoStatement->returns(['bindValue' => true]);
        assertTrue($this->pdoStatement->bindValue('hello', 'world'));
        verify($this->basePdoStatement, 'bindValue')
            ->received('hello', 'world', \PDO::PARAM_STR);
    }

    #[Test]
    public function failingBindValueThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['bindValue' => throws(new \PDOException('error'))]
        );
        expect(function() {
            $this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT);
        })->throws(DatabaseException::class);
    }

    #[Test]
    public function executeReturnsPdoQueryResult(): void
    {
        $this->basePdoStatement->returns(['execute' => true]);
        $result = $this->pdoStatement->execute([]);
        assertThat($result, isInstanceOf(PdoQueryResult::class));
    }

    #[Test]
    public function executePassesArguments(): void
    {
        $this->basePdoStatement->returns(['execute' => true]);
        $this->pdoStatement->execute([':roland' => 303]);
        verify($this->basePdoStatement, 'execute')
            ->received([':roland' => 303]);
    }

    #[Test]
    public function wrongExecuteThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(['execute' => false]);
        expect(function() { $this->pdoStatement->execute([]); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function failingExecuteThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['execute' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoStatement->execute(); })
            ->throws(DatabaseException::class);
    }

    #[Test]
    public function cleanClosesResultCursor(): void
    {
        $this->basePdoStatement->returns(['closeCursor' => true]);
        assertTrue($this->pdoStatement->clean());
    }

    #[Test]
    public function failingCleanThrowsDatabaseException(): void
    {
        $this->basePdoStatement->returns(
            ['closeCursor' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoStatement->clean(); })
            ->throws(DatabaseException::class);
    }
}
