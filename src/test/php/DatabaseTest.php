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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\sequence\assert\Provides;

use function bovigo\assert\{assertNull, assertThat};
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\onConsecutiveCalls;
/**
 * Test for stubbles\db\Database.
 *
 * @since  2.1.0
 */
#[Group('db')]
class DatabaseTest extends TestCase
{
    private Database $database;
    private DatabaseConnection&ClassProxy $dbConnection;

    /**
     * set up test environment
     */
    protected function setUp(): void
    {
        $this->dbConnection = NewInstance::of(DatabaseConnection::class);
        $this->database     = new Database($this->dbConnection);
    }

    private function createQueryResult(): QueryResult&ClassProxy
    {
        $statement   = NewInstance::of(Statement::class);
        $queryResult = NewInstance::of(QueryResult::class);
        $this->dbConnection->returns(['prepare' => $statement]);
        $statement->returns(['execute' => $queryResult]);
        return $queryResult;
    }

    /**
     * @since  3.1.0
     */
    #[Test]
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
     * @since  3.1.0
     */
    #[Test]
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

    #[Test]
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
     * @since  2.4.0
     */
    #[Test]
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
     * @since  9.0.2
     */
    #[Test]
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

    #[Test]
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
