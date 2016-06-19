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
use bovigo\callmap\NewInstance;

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\onConsecutiveCalls;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $dbConnection;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->dbConnection = NewInstance::of(DatabaseConnection::class);
        $this->database     = new Database($this->dbConnection);
    }

    /**
     * creates a mocked query result
     *
     * @return  \bovigo\callmap\Proxy
     */
    private function createQueryResult()
    {
        $statement   = NewInstance::of(Statement::class);
        $queryResult = NewInstance::of(QueryResult::class);
        $this->dbConnection->mapCalls(['prepare' => $statement]);
        $statement->mapCalls(['execute' => $queryResult]);
        return $queryResult;
    }

    /**
     * @test
     * @since   3.1.0
     */
    public function queryExecutesQueryAndReturnsAmountOfAffectedRecords()
    {
        $this->createQueryResult()->mapCalls(['count' => 1]);
        assert(
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
    public function fetchOneExecutesQueryAndReturnsOneValueFromGivenColumn()
    {
        $this->createQueryResult()->mapCalls(['fetchOne' => 'bar']);
        assert(
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
    public function fetchAllExecutesQueryAndFetchesCompleteResult()
    {
        $this->createQueryResult()->mapCalls(
                ['fetch' => onConsecutiveCalls(
                        ['foo' => 'bar', 'blubb' => '303'],
                        ['foo' => 'baz', 'blubb' => '909'],
                        false
                )]
        );
        assert(
                $this->database->fetchAll(
                        'SELECT foo, blubb FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )->data(),
                equals([
                        ['foo' => 'bar', 'blubb' => '303'],
                        ['foo' => 'baz', 'blubb' => '909']
                ])
        );
    }

    /**
     * @test
     * @since  2.4.0
     */
    public function fetchRowExecutesQueryAndFetchesFirstResultRow()
    {
        $this->createQueryResult()->mapCalls(['fetch' => ['foo' => 'bar']]);
        assert(
                $this->database->fetchRow(
                        'SELECT foo, blubb FROM baz WHERE col = :col',
                        [':col' => 'yes']
                ),
                equals(['foo' => 'bar'])
        );
    }

    /**
     * @test
     */
    public function fetchColumnExecutesQueryAndReturnsAllValuesFromColumn()
    {
        $this->createQueryResult()->mapCalls(
                ['fetchOne' => onConsecutiveCalls('bar', 'baz', false)]
        );
        assert(
                $this->database->fetchColumn(
                        'SELECT foo FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )->data(),
                equals(['bar', 'baz'])
        );
    }
}
