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
        assertEquals(
                ['foo', $bar, \PDO::PARAM_INT, 2, null],
                $this->basePdoStatement->argumentsReceivedFor('bindParam')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindParamThrowsDatabaseException()
    {
        $bar = 1;
        $this->basePdoStatement->mapCalls(
                ['bindParam' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2);
    }

    /**
     * @test
     */
    public function bindValuePassesValuesCorrectly()
    {
        $this->basePdoStatement->mapCalls(['bindValue' => true]);
        assertTrue($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
        assertEquals(
                ['foo', 1, \PDO::PARAM_INT],
                $this->basePdoStatement->argumentsReceivedFor('bindValue')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindValueThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['bindValue' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT);
    }

    /**
     * @test
     */
    public function executeReturnsPdoQueryResult()
    {
        $this->basePdoStatement->mapCalls(['execute' => true]);
        $result = $this->pdoStatement->execute([]);
        assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $result);
    }

    /**
     * @test
     */
    public function executePassesArguments()
    {
        $this->basePdoStatement->mapCalls(['execute' => true]);
        $this->pdoStatement->execute([':roland' => 303]);
        assertEquals(
                [[':roland' => 303]],
                $this->basePdoStatement->argumentsReceivedFor('execute')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function wrongExecuteThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(['execute' => false]);
        $this->pdoStatement->execute([]);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingExecuteThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['execute' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoStatement->execute();
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
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingCleanThrowsDatabaseException()
    {
        $this->basePdoStatement->mapCalls(
                ['closeCursor' => callmap\throws(new \PDOException('error'))]
        );
        $this->pdoStatement->clean();
    }
}
