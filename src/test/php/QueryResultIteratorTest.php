<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\onConsecutiveCalls;
/**
 * Test for stubbles\db\QueryResultIterator.
 *
 * @group  db
 * @since  5.0.0
 */
class QueryResultIteratorTest extends TestCase
{
    /**
     * mocked result to iterate over
     *
     * @var  QueryResult&\bovigo\callmap\ClassProxy
     */
    private $queryResult;

    protected function setUp(): void
    {
        $this->queryResult = NewInstance::of(QueryResult::class);
    }

    /**
     * create an instance for the test
     *
     * @param   mixed[]              $results
     * @param   int                  $fetchMode
     * @param   array<string,mixed>  $driverOptions
     * @return  \stubbles\db\QueryResultIterator
     */
    private function createIterator(
            array $results,
            int $fetchMode = \PDO::FETCH_ASSOC,
            array $driverOptions = []
    ): QueryResultIterator {
        $results[] = false;
        $this->queryResult->returns([
                'fetch'    => onConsecutiveCalls(...$results),
                'fetchOne' => onConsecutiveCalls('baz', false),
                'free'     => true
        ]);
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
    public function fetchColumnWithGivenColumn(): void
    {
        $results = [['foo', 'bar']];
        foreach ($this->createIterator($results, \PDO::FETCH_COLUMN, ['columnIndex' => 1]) as $result) {
            assertThat($result, equals('baz'));
        }
    }

    /**
     * @test
     */
    public function fetchColumnWithoutGivenColumn(): void
    {
        $results = [['foo', 'bar']];
        foreach ($this->createIterator($results, \PDO::FETCH_COLUMN) as $result) {
            assertThat($result, equals('baz'));
        }
    }

    /**
     * @test
     */
    public function canHandleEmptyResultSet(): void
    {
        $rounds = 0;
        foreach ($this->createIterator([]) as $result) {
            $rounds++;
        }

        assertThat($rounds, equals(0));
    }

    /**
     * @test
     */
    public function canIterateOnce(): void
    {
        $results = [['foo'], ['bar']];
        $rounds = 0;
        foreach ($this->createIterator($results) as $key => $result) {
            assertThat($result, equals($results[$key]));
            $rounds++;
        }

        assertThat($rounds, equals(2));
    }

    /**
     * @test
     */
    public function canNotIterateMoreThanOnce(): void
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
