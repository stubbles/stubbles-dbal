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
    private function createQueryResult($sql, array $values)
    {
        $mockStatement = $this->getMock('net\stubbles\db\Statement');
        $mockQueryResult = $this->getMock('net\stubbles\db\QueryResult');
        $this->mockDbConnection->expects($this->once())
                               ->method('prepare')
                               ->with($sql)
                               ->will($this->returnValue($mockStatement));
        $mockStatement->expects($this->once())
                      ->method('execute')
                      ->with($this->equalTo($values))
                      ->will($this->returnValue($mockQueryResult));
        return $mockQueryResult;
    }

    /**
     * @test
     */
    public function fetchAllExecutesQueryAndFetchesCompleteResult()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo, blubb FROM baz WHERE col = :col', [':col' => 'yes']);
        $mockQueryResult->expects($this->once())
                        ->method('fetchAll')
                        ->will($this->returnValue([['foo' => 'bar', 'blubb' => '303']]));
        $this->assertEquals([['foo' => 'bar', 'blubb' => '303']],
                            $this->database->fetchAll('SELECT foo, blubb FROM baz WHERE col = :col', [':col' => 'yes'])
        );
    }

    /**
     * @test
     * @since  2.4.0
     */
    public function fetchRowExecutesQueryAndFetchesFirstResultRow()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo, blubb FROM baz WHERE col = :col', [':col' => 'yes']);
        $mockQueryResult->expects($this->once())
                        ->method('fetch')
                        ->will($this->returnValue(['foo' => 'bar']));
        $this->assertEquals(['foo' => 'bar'],
                            $this->database->fetchRow('SELECT foo, blubb FROM baz WHERE col = :col', [':col' => 'yes'])
        );
    }

    /**
     * @test
     */
    public function fetchColumnExecutesQueryAndReturnsAllValuesFromColumn()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo FROM baz WHERE col = :col', [':col' => 'yes']);
        $mockQueryResult->expects($this->once())
                        ->method('fetchAll')
                        ->will($this->returnValue(['bar', 'blubb']));
        $this->assertEquals(['bar', 'blubb'],
                            $this->database->fetchColumn('SELECT foo FROM baz WHERE col = :col', [':col' => 'yes'])
        );
    }

    /**
     * @test
     */
    public function mapExecutesQueryAndAppliesFunctionToEachResultRow()
    {
        $mockQueryResult = $this->createQueryResult('SELECT foo FROM baz WHERE col = :col', [':col' => 'yes']);
        $mockQueryResult->expects($this->exactly(3))
                        ->method('fetch')
                        ->will($this->onConsecutiveCalls(['foo' => 'bar'], ['foo' => 'blubb'], false));
        $i = 0;
        $f = function($row) use (&$i)
        {
            $i++;
            if (1 === $i) {
                $this->assertEquals(['foo' => 'bar'], $row);
                return 303;
            }

            if (2 === $i) {
                $this->assertEquals(['foo' => 'blubb'], $row);
                return 313;
            }

            $this->fail('Unexpected call for row ' . var_export($row));
        };
        $this->assertEquals([303, 313],
                            $this->database->map('SELECT foo FROM baz WHERE col = :col', $f, [':col' => 'yes'])
        );
    }
}
