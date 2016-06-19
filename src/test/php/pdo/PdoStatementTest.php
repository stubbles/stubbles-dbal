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
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\contains;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
/**
 * Test for stubbles\db\pdo\PdoStatement.
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 */
class PdoStatementTest extends \PHPUnit_Framework_TestCase
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

    /**
     * set up test environment
     */
    public function setUp()
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
        $this->basePdoStatement->mapCalls(['bindParam' => true]);
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
        $this->basePdoStatement->mapCalls(
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
        $this->basePdoStatement->mapCalls(['bindValue' => true]);
        assertTrue($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
        verify($this->basePdoStatement, 'bindValue')
                ->received('foo', 1, \PDO::PARAM_INT);
    }

    /**
     * @test
     */
    public function failingBindValueThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
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
        $this->basePdoStatement->mapCalls(['execute' => true]);
        $result = $this->pdoStatement->execute([]);
        assert($result, isInstanceOf(PdoQueryResult::class));
    }

    /**
     * @test
     */
    public function executePassesArguments()
    {
        $this->basePdoStatement->mapCalls(['execute' => true]);
        $this->pdoStatement->execute([':roland' => 303]);
        verify($this->basePdoStatement, 'execute')
                ->received([':roland' => 303]);
    }

    /**
     * @test
     */
    public function wrongExecuteThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(['execute' => false]);
        expect(function() { $this->pdoStatement->execute([]); })
                ->throws(DatabaseException::class);
    }

    /**
     * @test
     */
    public function failingExecuteThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
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
        $this->basePdoStatement->mapCalls(['closeCursor' => true]);
        assertTrue($this->pdoStatement->clean());
    }

    /**
     * @test
     */
    public function failingCleanThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['closeCursor' => throws(new \PDOException('error'))]
        );
        expect(function() { $this->pdoStatement->clean(); })
                ->throws(DatabaseException::class);
    }
}
