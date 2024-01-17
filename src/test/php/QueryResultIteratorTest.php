<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\onConsecutiveCalls;
use function bovigo\callmap\throws;
/**
 * Test for stubbles\db\QueryResultIterator.
 *
 * @since  5.0.0
 */
#[Group('db')]
class QueryResultIteratorTest extends TestCase
{
    private QueryResult&ClassProxy $queryResult;

    protected function setUp(): void
    {
        $this->queryResult = NewInstance::of(QueryResult::class);
    }

    /**
     * create an instance for the test
     *
     * @param   mixed[]              $results
     * @param   array<string,mixed>  $driverOptions
     */
    private function createIterator(
            array $results,
            int $fetchMode = PDO::FETCH_ASSOC,
            array $driverOptions = []
    ): QueryResultIterator {
        $results[] = false;
        $this->queryResult->returns([
            'fetch'    => onConsecutiveCalls(...$results),
            'fetchOne' => onConsecutiveCalls('baz', false),
            'free'     => true
        ]);
        return new QueryResultIterator(
                $this->queryResult,
                $fetchMode,
                $driverOptions
        );
    }

    #[Test]
    public function fetchColumnWithGivenColumn(): void
    {
        $results = [['foo', 'bar']];
        foreach ($this->createIterator($results, \PDO::FETCH_COLUMN, ['columnIndex' => 1]) as $result) {
            assertThat($result, equals('baz'));
        }
    }

    #[Test]
    public function fetchColumnWithoutGivenColumn(): void
    {
        $results = [['foo', 'bar']];
        foreach ($this->createIterator($results, \PDO::FETCH_COLUMN) as $result) {
            assertThat($result, equals('baz'));
        }
    }

    #[Test]
    public function canHandleEmptyResultSet(): void
    {
        $rounds = 0;
        foreach ($this->createIterator([]) as $result) {
            $rounds++;
        }

        assertThat($rounds, equals(0));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function exceptionOnDestructionIsSwallowed(): void
    {
        $queryResultIterator = new QueryResultIterator($this->queryResult);
        $this->queryResult->returns(['free' => throws(new DatabaseException('failure'))]);
        expect(function() use ($queryResultIterator) { $queryResultIterator = null; })
            ->doesNotThrow();
    }
}
