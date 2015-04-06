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
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPdoStatement;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockPdoStatement = $this->getMock('\PDOStatement');
        $this->pdoStatement     = new PdoStatement($this->mockPdoStatement);
    }

    /**
     * @test
     */
    public function bindParamPassesValuesCorrectly()
    {
        $bar = 1;
        $this->mockPdoStatement->method('bindParam')
                ->with(equalTo('foo'), equalTo($bar), equalTo(\PDO::PARAM_INT))
                ->will(onConsecutiveCalls(true, false));
        $this->assertTrue($this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2));
        $this->assertFalse($this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2));
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindParamThrowsDatabaseException()
    {
        $bar = 1;
        $this->mockPdoStatement->method('bindParam')
                ->will(throwException(new \PDOException('error')));
        $this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2);
    }

    /**
     * @test
     */
    public function bindValuePassesValuesCorrectly()
    {
        $this->mockPdoStatement->method('bindValue')
                ->with(equalTo('foo'), equalTo(1), equalTo(\PDO::PARAM_INT))
                ->will(onConsecutiveCalls(true, false));
        $this->assertTrue($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
        $this->assertFalse($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingBindValueThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('bindValue')
                ->will(throwException(new \PDOException('error')));
        $this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT);
    }

    /**
     * @test
     */
    public function executeReturnsPdoQueryResult()
    {
        $this->mockPdoStatement->method('execute')
                ->with(equalTo([]))
                ->will(returnValue(true));
        $result = $this->pdoStatement->execute([]);
        $this->assertInstanceOf('stubbles\db\pdo\PdoQueryResult', $result);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function wrongExecuteThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('execute')
                ->with(equalTo([]))
                ->will(returnValue(false));
        $this->pdoStatement->execute([]);
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingExecuteThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('execute')
                ->with(equalTo([]))
                ->will(throwException(new \PDOException('error')));
        $this->pdoStatement->execute();
    }

    /**
     * @test
     */
    public function cleanClosesResultCursor()
    {
        $this->mockPdoStatement->method('closeCursor')->will(returnValue(true));
        $this->assertTrue($this->pdoStatement->clean());
    }

    /**
     * @test
     * @expectedException  stubbles\db\DatabaseException
     */
    public function failingCleanThrowsDatabaseException()
    {
        $this->mockPdoStatement->method('closeCursor')
                ->will(throwException(new \PDOException('error')));
        $this->pdoStatement->clean();
    }
}
