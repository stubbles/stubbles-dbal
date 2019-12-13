<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\db;
/**
 * Allows to iterate over the query result.
 *
 * @since  5.0.0
 * @implements  \Iterator<mixed>
 */
class QueryResultIterator implements \Iterator
{
    /**
     * @var  \stubbles\db\QueryResult
     */
    private $queryResult;
    /**
     * @var  int
     */
    private $fetchMode;
    /**
     * @var  array<string,mixed>
     */
    private $driverOptions;
    /**
     * @var  mixed
     */
    private $current;
    /**
     * @var  int
     */
    private $key = -1;

    /**
     * constructor
     *
     * @param  \stubbles\db\QueryResult  $queryResult    actual query result
     * @param  int                       $fetchMode      mode to use for fetching the data
     * @param  array<string,mixed>       $driverOptions  map of driver specific arguments
     */
    public function __construct(
            QueryResult $queryResult,
            int $fetchMode = null,
            array $driverOptions = []
    ) {
        $this->queryResult   = $queryResult;
        $this->fetchMode     = $fetchMode;
        $this->driverOptions = $driverOptions;
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        try {
            $this->queryResult->free();
        } catch (DatabaseException $dbe) {
            // ignore, can't throw exceptions from destructor
        }
    }

    /**
     * returns current result entry
     *
     * @return  mixed
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * returns current result key
     *
     * @return  int
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * iterates to next result element
     */
    public function next(): void
    {
        $this->key++;
        if (\PDO::FETCH_COLUMN !== $this->fetchMode) {
            $this->current = $this->queryResult->fetch(
                    $this->fetchMode,
                    $this->driverOptions
            );
        } else {
            $this->current = $this->queryResult->fetchOne(
                    $this->driverOptions['columnIndex'] ?? 0
            );
        }
    }

    /**
     * it's not possible to rewind a query result because you can't reset a database result set
     *
     * @throws  \BadMethodCallException
     */
    public function rewind(): void
    {
        if (null === $this->current) {
            $this->next();
        } else {
            throw new \BadMethodCallException('Can not rewind database result set');
        }
    }

    /**
     * checks if current result entry is valid
     *
     * @return  bool
     */
    public function valid(): bool
    {
        return false !== $this->current;
    }
}
