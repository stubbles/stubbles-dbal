<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db;
/**
 * Test for stubbles\db\Database.
 *
 * @group  db
 * @since  2.1.0
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase
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
        $this->mockDbConnection = $this->getMock('stubbles\db\DatabaseConnection');
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
        $mockStatement = $this->getMock('stubbles\db\Statement');
        $mockQueryResult = $this->getMock('stubbles\db\QueryResult');
        $this->mockDbConnection->method('prepare')
                ->with($sql)
                ->will($this->returnValue($mockStatement));
        $mockStatement->method('execute')
                ->with($this->equalTo($values))
                ->will($this->returnValue($mockQueryResult));
        return $mockQueryResult;
    }

    /**
     * @test
     * @since   3.1.0
     */
    public function queryExecutesQueryAndReturnsAmountOfAffectedRecords()
    {
        $mockQueryResult = $this->createQueryResult(
                'INSERT INTO baz VALUES (:col)',
                [':col' => 'yes']
        );
        $mockQueryResult->method('count')->will(returnValue(1));
        assertEquals(
                1,
                $this->database->query(
                        'INSERT INTO baz VALUES (:col)',
                        [':col' => 'yes']
                )
        );
    }

    /**
     * @test
     * @since   3.1.0
     */
    public function fetchOneExecutesQueryAndReturnsOneValueFromGivenColumn()
    {
        $mockQueryResult = $this->createQueryResult(
                'SELECT foo FROM baz WHERE col = :col',
                [':col' => 'yes']
        );
        $mockQueryResult->method('fetchOne')
                ->with(equalTo(0))
                ->will(returnValue('bar'));
        assertEquals(
                'bar',
                $this->database->fetchOne(
                        'SELECT foo FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )
        );
    }

    /**
     * @test
     */
    public function fetchAllExecutesQueryAndFetchesCompleteResult()
    {
        $mockQueryResult = $this->createQueryResult(
                'SELECT foo, blubb FROM baz WHERE col = :col',
                [':col' => 'yes']
        );
        $mockQueryResult->method('fetch')
                ->will(onConsecutiveCalls(
                        ['foo' => 'bar', 'blubb' => '303'],
                        ['foo' => 'baz', 'blubb' => '909'],
                        false
                ));
        assertEquals(
                [['foo' => 'bar', 'blubb' => '303'],
                 ['foo' => 'baz', 'blubb' => '909']
                ],
                $this->database->fetchAll(
                        'SELECT foo, blubb FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )->data()
        );
    }

    /**
     * @test
     * @since  2.4.0
     */
    public function fetchRowExecutesQueryAndFetchesFirstResultRow()
    {
        $mockQueryResult = $this->createQueryResult(
                'SELECT foo, blubb FROM baz WHERE col = :col',
                [':col' => 'yes']
        );
        $mockQueryResult->method('fetch')->will(returnValue(['foo' => 'bar']));
        assertEquals(
                ['foo' => 'bar'],
                $this->database->fetchRow(
                        'SELECT foo, blubb FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )
        );
    }

    /**
     * @test
     */
    public function fetchColumnExecutesQueryAndReturnsAllValuesFromColumn()
    {
        $mockQueryResult = $this->createQueryResult(
                'SELECT foo FROM baz WHERE col = :col',
                [':col' => 'yes']
        );
        $mockQueryResult->method('fetchOne')
                ->will(onConsecutiveCalls('bar', 'baz', false));
        assertEquals(
                ['bar', 'baz'],
                $this->database->fetchColumn(
                        'SELECT foo FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )->data()
        );
    }

    /**
     * @test
     */
    public function mapExecutesQueryAndAppliesFunctionToEachResultRow()
    {
        $mockQueryResult = $this->createQueryResult(
                'SELECT foo FROM baz WHERE col = :col',
                [':col' => 'yes']
        );
        $mockQueryResult->method('fetch')
                ->will($this->onConsecutiveCalls(
                        ['foo' => 'bar'],
                        ['foo' => 'blubb'],
                        false
                ));
        $i = 0;
        $f = function($row) use (&$i)
        {
            $i++;
            if (1 === $i) {
                assertEquals(['foo' => 'bar'], $row);
                return 303;
            }

            if (2 === $i) {
                assertEquals(['foo' => 'blubb'], $row);
                return 313;
            }

            $this->fail('Unexpected call for row ' . var_export($row));
        };
        assertEquals(
                [303, 313],
                $this->database->map(
                        'SELECT foo FROM baz WHERE col = :col',
                        $f,
                        [':col' => 'yes']
                )
        );
    }
}
