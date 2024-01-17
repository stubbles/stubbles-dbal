<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db\pdo;

use BadMethodCallException;
use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use Generator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement as PhpPdoStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use stubbles\db\DatabaseException;
use stubbles\db\config\DatabaseConfiguration;

use function bovigo\assert\{
    assertThat,
    assertTrue,
    expect,
    predicate\contains,
    predicate\equals,
    predicate\isInstanceOf
};
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
/**
 * Test for stubbles\db\pdo\PdoDatabaseConnection.
 */
#[Group('db')]
#[Group('pdo')]
#[RequiresPhpExtension('pdo')]
#[RequiresPhpExtension('pdo_sqlite')]
class PdoDatabaseConnectionTest extends TestCase
{
    private PdoDatabaseConnection $pdoConnection;
    private DatabaseConfiguration $dbConfig;
    private PDO&ClassProxy $pdo;

    protected function setUp(): void
    {
        $this->pdo      = NewInstance::of(PDO::class, ['sqlite::memory:']);
        $this->dbConfig = DatabaseConfiguration::fromArray(
            'foo', 'dsn:bar', ['baz' => 'bar']
        );
        $this->pdoConnection = new PdoDatabaseConnection(
            $this->dbConfig,
            fn(): PDO&ClassProxy => $this->pdo
        );
    }

    protected function tearDown(): void
    {
        unset($this->pdo);
    }

    /**
     * @since  2.1.0
     */
    #[Test]
    public function dsnReturnsDsnFromConfiguration(): void
    {
        assertThat($this->pdoConnection->dsn(), equals('dsn:bar'));
    }

    /**
     * @since  2.1.0
     */
    #[Test]
    public function detailsReturnsDetailsFromConfiguration(): void
    {
        $this->dbConfig->setDetails('some interesting details about the db');
        assertThat(
                $this->pdoConnection->details(),
                equals('some interesting details about the db')
        );
    }

    /**
     * @since  2.2.0
     */
    #[Test]
    public function propertyReturnsPropertyFromConfiguration(): void
    {
        assertThat($this->pdoConnection->property('baz'), equals('bar'));
    }

    /**
     * assert that a call to an undefined pdo method throws a MethodInvocationException
     */
    #[Test]
    public function undefinedMethod(): void
    {
        expect(function() { $this->pdoConnection->foo('bar'); })
            ->throws(BadMethodCallException::class)
            ->withMessage(
                'Call to undefined method ' . PdoDatabaseConnection::class
                . '::foo()'
            );
    }

    #[Test]
    public function connectWithoutInitialQuery(): void
    {
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->wasNeverCalled();
    }

    #[Test]
    public function connectExecutesInitialQuery(): void
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->returns(['query' => true]);
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->received('set names utf8');
    }

    #[Test]
    public function connectExecutesInitialQueryOnlyOnce(): void
    {
        $this->dbConfig->setInitialQuery('set names utf8');
        $this->pdo->returns(['query' => true]);
        $this->pdoConnection->connect();
        $this->pdoConnection->connect();
        verify($this->pdo, 'query')->wasCalledOnce();
    }

    #[Test]
    public function connectThrowsDatabaseExceptionWhenPdoFails(): void
    {
        $this->pdoConnection = new PdoDatabaseConnection(
            $this->dbConfig,
            function()
            {
                throw new \PDOException('error');
            }
        );
        expect(function() { $this->pdoConnection->connect(); })
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    public static function methodCalls(): Generator
    {
        yield [
            'beginTransaction',
            true,
            function(PdoDatabaseConnection $pdoConnection)
            {
                assertTrue($pdoConnection->beginTransaction());
            }
        ];
        yield [
            'commit',
            true,
            function(PdoDatabaseConnection $pdoConnection)
            {
                assertTrue($pdoConnection->commit());
            }
        ];
        yield [
            'rollBack',
            true,
            function(PdoDatabaseConnection $pdoConnection)
            {
                assertTrue($pdoConnection->rollback());
            }
        ];
        yield [
            'exec',
            66,
            function(PdoDatabaseConnection $pdoConnection)
            {
                assertThat($pdoConnection->exec('foo'), equals(66));
            }
        ];
        yield ['lastInsertId',
            '5',
            function(PdoDatabaseConnection $pdoConnection)
            {
                $pdoConnection->connect(); // must be connected
                assertThat($pdoConnection->getLastInsertId(), equals(5));
            }
        ];
    }

    #[Test]
    #[DataProvider('methodCalls')]
    public function delegatesMethodCallsToPdoInstance(
        string $method,
        mixed $returnValue,
        callable $assert
    ): void {
        $this->pdo->returns([$method => $returnValue]);
        $assert($this->pdoConnection);
    }

    private function callThrowsException(string $method): void
    {
        $this->pdo->returns([
            $method => throws(new PDOException('error'))
        ]);
    }

    #[Test]
    public function delegatedMethodCallWrapsPdoExceptionToDatabaseException(): void
    {
        $this->callThrowsException('commit');
        expect(function() { $this->pdoConnection->commit(); })
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    #[Test]
    public function prepareDelegatesToPdoInstanceAndReturnsPdoStatement(): void
    {
        $this->pdo->returns(['prepare' => NewInstance::of(PhpPdoStatement::class)]);
        assertThat(
            $this->pdoConnection->prepare('foo'),
            isInstanceOf(PdoStatement::class)
        );
        verify($this->pdo, 'prepare')->received('foo', []);
    }

    #[Test]
    public function prepareThrowsDatabaseExceptionWhenStatementCreationFails(): void
    {
        $this->callThrowsException('prepare');
        expect(function() { $this->pdoConnection->prepare('foo'); })
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    #[Test]
    public function queryWithOutFetchMode(): void
    {
        $this->pdo->returns(['query' => NewInstance::of(PhpPdoStatement::class)]);
        assertThat(
            $this->pdoConnection->query('foo'),
            isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo');
    }

    #[Test]
    public function queryWithNoSpecialFetchMode(): void
    {
        $this->pdo->returns(['query' => NewInstance::of(PhpPdoStatement::class)]);
        assertThat(
            $this->pdoConnection->query(
                'foo',
                ['fetchMode' => \PDO::FETCH_ASSOC]
            ),
            isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo', \PDO::FETCH_ASSOC);
    }

    #[Test]
    public function queryWithFetchModeColumn(): void
    {
        $this->pdo->returns(['query' => NewInstance::of(PhpPdoStatement::class)]);
        assertThat(
            $this->pdoConnection->query(
                'foo',
                ['fetchMode' => PDO::FETCH_COLUMN, 'colNo' => 5]
            ),
            isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')->received('foo', PDO::FETCH_COLUMN, 5);
    }

    #[Test]
    public function queryWithFetchModeColumnButMissingOptionThrowsIllegalArgumentException(): void
    {
        expect(function() {
            $this->pdoConnection->query('foo', ['fetchMode' => PDO::FETCH_COLUMN]);
        })->throws(\InvalidArgumentException::class);
    }

    #[Test]
    public function queryWithFetchModeInto(): void
    {
        $this->pdo->returns(['query' => NewInstance::of(PhpPdoStatement::class)]);
        $class = new stdClass();
        assertThat(
            $this->pdoConnection->query(
                'foo',
                ['fetchMode' => PDO::FETCH_INTO, 'object' => $class]
            ),
            isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')
            ->received('foo', PDO::FETCH_INTO, $class);
    }

    #[Test]
    public function queryWithFetchModeIntoButMissingOptionThrowsIllegalArgumentException(): void
    {
        expect(function() {
            $this->pdoConnection->query('foo', ['fetchMode' => PDO::FETCH_INTO]);
        })->throws(InvalidArgumentException::class);
    }

    #[Test]
    public function queryWithFetchModeClass(): void
    {
        $this->pdo->returns(['query' => NewInstance::of(PhpPdoStatement::class)]);
        assertThat(
            $this->pdoConnection->query(
                'foo',
                ['fetchMode' => PDO::FETCH_CLASS, 'classname' => 'MyClass']
            ),
            isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')
                ->received('foo', PDO::FETCH_CLASS, 'MyClass', []);
    }

    #[Test]
    public function queryWithFetchModeClassButMissingOptionThrowsIllegalArgumentException(): void
    {
        expect(function() {
            $this->pdoConnection->query('foo', ['fetchMode' => PDO::FETCH_CLASS]);
        })->throws(InvalidArgumentException::class);
    }

    #[Test]
    public function queryWithFetchModeClassWithCtorArgs(): void
    {
        $this->pdo->returns(['query' => NewInstance::of(PhpPdoStatement::class)]);
        assertThat(
            $this->pdoConnection->query(
                'foo',
                [
                    'fetchMode' => PDO::FETCH_CLASS,
                    'classname' => 'MyClass',
                    'ctorargs' => ['foo']
                ]
            ),
            isInstanceOf(PdoQueryResult::class)
        );
        verify($this->pdo, 'query')
            ->received('foo', PDO::FETCH_CLASS, 'MyClass', ['foo']);
    }

    #[Test]
    public function queryThrowsDatabaseExceptionOnFailure(): void
    {
        $this->callThrowsException('query');
        expect(fn() => $this->pdoConnection->query('foo'))
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    #[Test]
    public function execThrowsDatabaseExceptionOnFailure(): void
    {
        $this->callThrowsException('exec');
        expect(fn() => $this->pdoConnection->exec('foo'))
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }

    #[Test]
    public function getLastInsertIdThrowsDatabaseExceptionWhenNotConnected(): void
    {
        expect(fn() => $this->pdoConnection->getLastInsertId())
            ->throws(DatabaseException::class)
            ->withMessage('Not connected: can not retrieve last insert id');
    }

    #[Test]
    public function getLastInsertIdThrowsDatabaseExceptionWhenPdoCallFails(): void
    {
        $this->callThrowsException('lastInsertId');
        expect(fn() => $this->pdoConnection->connect()->getLastInsertId())
            ->throws(DatabaseException::class)
            ->message(contains('error'));
    }
}
