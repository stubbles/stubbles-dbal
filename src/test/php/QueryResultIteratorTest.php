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
 * Test for stubbles\db\QueryResultIterator.
 *
 * @group  db
 * @since  5.0.0
 */
class QueryResultIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked result to iterate over
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockQueryResult;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockQueryResult     = $this->getMock('stubbles\db\QueryResult');
    }

    private function createIterator(array $results)
    {
        $i = 0;
        foreach ($results as $result) {
            $this->mockQueryResult->expects(at($i))
                    ->method('fetch')
                    ->will(returnValue($result));
            $i++;
        }
        $this->mockQueryResult->expects(at($i))
                ->method('fetch')
                ->will(onConsecutiveCalls(false));
        $queryResultIterator = new QueryResultIterator(
                $this->mockQueryResult,
                \PDO::FETCH_ASSOC,
                []
        );
        return $queryResultIterator;
    }

    /**
     * @test
     */
    public function canHandleEmptyResultSet()
    {
        $rounds = 0;
        foreach ($this->createIterator([]) as $result) {
            $rounds++;
        }

        assertEquals(0, $rounds);
    }

    /**
     * @test
     */
    public function canIterateOnce()
    {
        $results = [['foo'], ['bar']];
        $rounds = 0;
        foreach ($this->createIterator($results) as $key => $result) {
            assertEquals($results[$key], $result);
            $rounds++;
        }

        assertEquals(2, $rounds);
    }

    /**
     * @test
     * @expectedException  BadMethodCallException
     */
    public function canNotIterateMoreThanOnce()
    {
        $iterator = $this->createIterator([['foo'], ['bar']]);
        foreach ($iterator as $result) {
            // do nothing
        }

        foreach ($iterator as $result) {
            // do nothing
        }
    }
}
