<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db;
/**
 * Test for net\stubbles\db\Database.
 *
 * @group  db
 */
class DatabaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  Database
     */
    private $database;
    /**
     * mocked database connection
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockDbConnection;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockDbConnection = $this->getMock('net\stubbles\db\DatabaseConnection');
        $this->database         = new Database($this->mockDbConnection);
    }

    /**
     * creates a mocked query result
     *
     * @param   string  $sql
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function createQueryResult($sql)
    {
        $mockQueryResult = $this->getMock('net\stubbles\db\QueryResult');
        $this->mockDbConnection->expects($this->once())
                               ->method('query')
                               ->with($sql)
                               ->will($this->returnValue($mockQueryResult));
        return $mockQueryResult;
    }

    /**
     * @test
     */
    public function fetchAllExecutesQueryAndFetchesCompleteResult()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo, blubb FROM baz');
        $mockQueryResult->expects($this->once())
                        ->method('fetchAll')
                        ->will($this->returnValue([['foo' => 'bar', 'blubb' => '303']]));
        $this->assertEquals([['foo' => 'bar', 'blubb' => '303']],
                            $this->database->fetchAll('SELECT foo, blubb FROM baz')
        );
    }

    /**
     * @test
     */
    public function fetchColumnExecutesQueryAndReturnsAllValuesFromColumn()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo FROM baz');
        $mockQueryResult->expects($this->once())
                        ->method('fetchAll')
                        ->will($this->returnValue(['bar', 'blubb']));
        $this->assertEquals(['bar', 'blubb'],
                            $this->database->fetchColumn('SELECT foo FROM baz')
        );
    }

    /**
     * @test
     */
    public function applyExecutesQueryAndAppliesFunctionToEachResultRow()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo FROM baz');
        $mockQueryResult->expects($this->exactly(3))
                        ->method('fetch')
                        ->will($this->onConsecutiveCalls(['foo' => 'bar'], ['foo' => 'blubb'], false));
        $i = 0;
        $f = function($row) use (&$i)
        {
            if (0 === $i) {
                $this->assertEquals(['foo' => 'bar'], $row);
            } elseif (1 === $i) {
                $this->assertEquals(['foo' => 'blubb'], $row);
            } else {
                $this->fail('Unexpected call for row ' . var_export($row));
            }

            $i++;
        };
        $this->database->apply('SELECT foo FROM baz', $f);
    }
}
