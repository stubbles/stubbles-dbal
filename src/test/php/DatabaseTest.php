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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
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
        $this->dbConnection = NewInstance::of('stubbles\db\DatabaseConnection');
        $this->database     = new Database($this->dbConnection);
    }

    /**
     * creates a mocked query result
     *
     * @return  \bovigo\callmap\Proxy
     */
    private function createQueryResult()
    {
        $statement   = NewInstance::of('stubbles\db\Statement');
        $queryResult = NewInstance::of('stubbles\db\QueryResult');
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
        $this->createQueryResult()->mapCalls(['fetchOne' => 'bar']);
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
        $this->createQueryResult()->mapCalls(
                ['fetch' => callmap\onConsecutiveCalls(
                        ['foo' => 'bar', 'blubb' => '303'],
                        ['foo' => 'baz', 'blubb' => '909'],
                        false
                )]
        );
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
        $this->createQueryResult()->mapCalls(['fetch' => ['foo' => 'bar']]);
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
        $this->createQueryResult()->mapCalls(
                ['fetchOne' => callmap\onConsecutiveCalls('bar', 'baz', false)]
        );
        assertEquals(
                ['bar', 'baz'],
                $this->database->fetchColumn(
                        'SELECT foo FROM baz WHERE col = :col',
                        [':col' => 'yes']
                )->data()
        );
    }
}
