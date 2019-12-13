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
use stubbles\sequence\assert\Provides;

use function bovigo\assert\{assertNull, assertThat};
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\onConsecutiveCalls;
/**
 * Test for stubbles\db\Database.
 *
 * @group  db
 * @since  2.1.0
 */
class DatabaseTest extends TestCase
{
    /**
     * instance to test
     *
     * @var  Database
     */
    private $database;
    /**
     * mocked database connection
     *
     * @var  DatabaseConnection&\bovigo\callmap\ClassProxy
     */
    private $dbConnection;

    /**
     * set up test environment
     */
    protected function setUp(): void
    {
        $this->dbConnection = NewInstance::of(DatabaseConnection::class);
        $this->database     = new Database($this->dbConnection);
    }

    /**
     * @return  QueryResult&\bovigo\callmap\ClassProxy
     */
    private function createQueryResult(): QueryResult
    {
        $statement   = NewInstance::of(Statement::class);
        $queryResult = NewInstance::of(QueryResult::class);
        $this->dbConnection->returns(['prepare' => $statement]);
        $statement->returns(['execute' => $queryResult]);
        return $queryResult;
    }

    /**
     * @test
     * @since   3.1.0
     */
    public function queryExecutesQueryAndReturnsAmountOfAffectedRecords(): void
    {
        $this->createQueryResult()->returns(['count' => 1]);
        assertThat(
                $this->database->query(
                        'INSERT INTO baz VALUES (:col)',
                        [':col' => 'yes']
                ),
                equals(1)
        );
    }

    /**
     * @test
     * @since   3.1.0
     */
    public function fetchOneExecutesQueryAndReturnsOneValueFromGivenColumn(): void
    {
        $this->createQueryResult()->returns(['fetchOne' => 'bar']);
        assertThat(
                $this->database->fetchOne(
                        'SELECT foo FROM baz WHERE col = :col',
                        [':col' => 'yes']
                ),
                equals('bar')
        );
    }

    /**
     * @test
     */
    public function fetchAllExecutesQueryAndFetchesCompleteResult(): void
    {
        $this->createQueryResult()->returns([
                'fetch' => onConsecutiveCalls(
                        ['foo' => 'bar', 'blubb' => '303'],
                        ['foo' => 'baz', 'blubb' => '909'],
                        false
                ),
                'free'  => true
        ]);
        assertThat(
                $this->database->fetchAll(
                        'SELECT foo, blubb FROM baz WHERE col = :col',
                        [':col' => 'yes']
                ),
                Provides::data([
                        ['foo' => 'bar', 'blubb' => '303'],
                        ['foo' => 'baz', 'blubb' => '909']
                ])
        );
    }

    /**
     * @test
     * @since  2.4.0
     */
    public function fetchRowExecutesQueryAndFetchesFirstResultRow(): void
    {
        $this->createQueryResult()->returns([
                'fetch' => ['foo' => 'bar'],
                'free'  => true
        ]);
        assertThat(
                $this->database->fetchRow(
                        'SELECT foo, blubb FROM baz WHERE col = :col',
                        [':col' => 'yes']
                ),
                equals(['foo' => 'bar'])
        );
    }

    /**
     * @test
     * @since  9.0.2
     */
    public function fetchRowReturnsNullWhenUnderlyingConnectionReturnsFalse(): void
    {
        $this->createQueryResult()->returns([
            'fetch' => false,
            'free'  => true
        ]);
        assertNull(
            $this->database->fetchRow(
                'SELECT foo, blubb FROM baz WHERE col = :col',
                [':col' => 'yes']
            )
        );
    }

    /**
     * @test
     */
    public function fetchColumnExecutesQueryAndReturnsAllValuesFromColumn(): void
    {
        $this->createQueryResult()->returns([
                'fetchOne' => onConsecutiveCalls('bar', 'baz', false),
                'free'     => true
        ]);
        assertThat(
                $this->database->fetchColumn(
                        'SELECT foo FROM baz WHERE col = :col',
                        [':col' => 'yes']
                ),
                Provides::data(['bar', 'baz'])
        );
    }
}
