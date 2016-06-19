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
 * Exceptions on calls to the database.
 */
class DatabaseException extends \Exception
{
    /**
     * constructor
     *
     * @param  string      $message   exception message
     * @param  \Exception  $previous  exception that caused this exception
     */
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
