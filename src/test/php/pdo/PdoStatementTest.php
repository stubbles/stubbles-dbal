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
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 */
class PdoStatementTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  PdoStatement
     */
    private $pdoStatement;
    /**
     * mock for pdo
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $basePdoStatement;

    protected function setUp(): void
    {
        $this->basePdoStatement = NewInstance::of('\PDOStatement');
        $this->pdoStatement     = new PdoStatement($this->basePdoStatement);
    }

    /**
     * @test
     */
    public function bindParamPassesValuesCorrectly()
    {
        $bar = 1;
        $this->basePdoStatement->returns(['bindParam' => true]);
        assertTrue($this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2));
        verify($this->basePdoStatement, 'bindParam')
                ->received('foo', $bar, \PDO::PARAM_INT, 2, null);
    }

    /**
     * @test
     */
    public function failingBindParamThrowsDatabaseException()
    {
        $bar = 1;
        $this->basePdoStatement->returns(
                ['bindParam' => throws(new \PDOException('error'))]
        );
        expect(function() {
                $this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2);
        })->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function bindValuePassesValuesCorrectly()
    {
        $this->basePdoStatement->returns(['bindValue' => true]);
        assertTrue($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
        verify($this->basePdoStatement, 'bindValue')
                ->received('foo', 1, \PDO::PARAM_INT);
    }

    /**
     * @test
     */
    public function failingBindValueThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
                ['bindValue' => throws(new \PDOException('error'))]
        );
        expect(function() {
                $this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT);
        })->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function executeReturnsPdoQueryResult()
    {
        $this->basePdoStatement->returns(['execute' => true]);
        $result = $this->pdoStatement->execute([]);
        assertThat($result, isInstanceOf(PdoQueryResult::class));
    }

    /**
     * @test
     */
    public function executePassesArguments()
    {
        $this->basePdoStatement->returns(['execute' => true]);
        $this->pdoStatement->execute([':roland' => 303]);
        verify($this->basePdoStatement, 'execute')
                ->received([':roland' => 303]);
    }

    /**
     * @test
     */
    public function wrongExecuteThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(['execute' => false]);
        expect(function() { $this->pdoStatement->execute([]); })
                ->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function failingExecuteThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
                ['execute' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoStatement->execute(); })
                ->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function cleanClosesResultCursor()
    {
        $this->basePdoStatement->returns(['closeCursor' => true]);
        assertTrue($this->pdoStatement->clean());
    }

    /**
     * @test
     */
    public function failingCleanThrowsDatabaseException()
    {
        $this->basePdoStatement->returns(
                ['closeCursor' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoStatement->clean(); })
                ->throws(DatabaseException::class);
    }
}
