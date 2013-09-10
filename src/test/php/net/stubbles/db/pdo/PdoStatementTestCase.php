<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\pdo;
/**
 * Test for net\stubbles\db\pdo\PdoDatabaseConnection.
 *
 * @group     db
 * @group     pdo
 * @requires  extension pdo
 */
class PdoStatementTestCase extends \PHPUnit_Framework_TestCase
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
        $this->mockPdoStatement->expects($this->exactly(2))
                               ->method('bindParam')
                               ->with($this->equalTo('foo'), $this->equalTo($bar), $this->equalTo(\PDO::PARAM_INT))
                               ->will($this->onConsecutiveCalls(true, false));
        $this->assertTrue($this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2));
        $this->assertFalse($this->pdoStatement->bindParam('foo', $bar, \PDO::PARAM_INT, 2));
    }

    /**
     * @test
     */
    public function bindValuePassesValuesCorrectly()
    {
        $this->mockPdoStatement->expects($this->exactly(2))
                               ->method('bindValue')
                               ->with($this->equalTo('foo'), $this->equalTo(1), $this->equalTo(\PDO::PARAM_INT))
                               ->will($this->onConsecutiveCalls(true, false));
        $this->assertTrue($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
        $this->assertFalse($this->pdoStatement->bindValue('foo', 1, \PDO::PARAM_INT));
    }

    /**
     * @test
     */
    public function executeReturnsPdoQueryResult()
    {
        $this->mockPdoStatement->expects($this->once())
                               ->method('execute')
                               ->with($this->equalTo(array()))
                               ->will($this->returnValue(true));
        $result = $this->pdoStatement->execute(array());
        $this->assertInstanceOf('net\stubbles\db\pdo\PdoQueryResult', $result);
    }

    /**
     * @test
     * @expectedException  net\stubbles\db\DatabaseException
     */
    public function failingExecuteThrowsDatabaseException()
    {
        $this->mockPdoStatement->expects($this->once())
                               ->method('execute')
                               ->with($this->equalTo(array()))
                               ->will($this->returnValue(false));
        $this->pdoStatement->execute(array());
    }

    /**
     * @test
     */
    public function cleanClosesResultCursor()
    {
        $this->mockPdoStatement->expects($this->once())
                               ->method('closeCursor')
                               ->will($this->returnValue(true));
        $this->assertTrue($this->pdoStatement->clean());
    }

    /**
     * @test
     * @expectedException  net\stubbles\db\DatabaseException
     */
    public function failingCleanThrowsDatabaseException()
    {
        $this->mockPdoStatement->expects($this->once())
                               ->method('closeCursor')
                               ->will($this->throwException(new \PDOException('error')));
        $this->pdoStatement->clean();
    }
}