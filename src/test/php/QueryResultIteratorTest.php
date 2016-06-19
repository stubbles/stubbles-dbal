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
use bovigo\callmap\InvocationResults;
use bovigo\callmap\NewInstance;

use function bovigo\assert\assert;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $queryResult;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->queryResult = NewInstance::of(QueryResult::class);
    }

    /**
     * create an instance for the test
     *
     * @param   mixed[]  $results
     * @return  \stubbles\db\QueryResultIterator
     */
    private function createIterator(
            array $results,
            $fetchMode = \PDO::FETCH_ASSOC,
            array $driverOptions = [])
    {
        $results[] = false;
        $this->queryResult->mapCalls(
                // FIXME replace by callmap\onConsecutiveCalls(...$results)
                // as soon as PHP 5.6 is the minimum version, as
                // InvocationResults doesn't belong to the public API
                ['fetch'    => new InvocationResults($results),
                 'fetchOne' => new InvocationResults(['baz', false])
                ]
        );
        $queryResultIterator = new QueryResultIterator(
                $this->queryResult,
                $fetchMode,
                $driverOptions
        );
        return $queryResultIterator;
    }

    /**
     * @test
     */
    public function fetchColumnWithGivenColumn()
    {
        $results = [['foo', 'bar']];
        foreach ($this->createIterator($results, \PDO::FETCH_COLUMN, ['columnIndex' => 1]) as $result) {
            assert($result, equals('baz'));
        }
    }

    /**
     * @test
     */
    public function fetchColumnWithoutGivenColumn()
    {
        $results = [['foo', 'bar']];
        foreach ($this->createIterator($results, \PDO::FETCH_COLUMN) as $result) {
            assert($result, equals('baz'));
        }
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

        assert($rounds, equals(0));
    }

    /**
     * @test
     */
    public function canIterateOnce()
    {
        $results = [['foo'], ['bar']];
        $rounds = 0;
        foreach ($this->createIterator($results) as $key => $result) {
            assert($result, equals($results[$key]));
            $rounds++;
        }

        assert($rounds, equals(2));
    }

    /**
     * @test
     */
    public function canNotIterateMoreThanOnce()
    {
        $iterator = $this->createIterator([['foo'], ['bar']]);
        foreach ($iterator as $result) {
            // do nothing
        }

        expect(function() use($iterator) {
                foreach ($iterator as $result) {
                    // do nothing
                }
        })
                ->throws(\BadMethodCallException::class)
                ->withMessage('Can not rewind database result set');
    }
}
