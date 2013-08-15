<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\db
 */
namespace net\stubbles\db\ioc;
use net\stubbles\ioc\Binder;
use net\stubbles\ioc\module\BindingModule;
/**
 * Binding module for the database package.
 */
class DatabaseBindingModule implements BindingModule
{
    /**
     * whether to allow fallback to default connection if named connection does not exist
     *
     * @type  bool
     */
    private $fallback;
    /**
     * descriptor to be used for initializer
     *
     * @type  string
     */
    private $descriptor;
    /**
     * name of database initializer class to bind
     *
     * @type  string
     */
    private $configReaderClass = 'net\stubbles\db\config\PropertyBasedDatabaseConfigReader';

    /**
     * constructor
     *
     * @param  bool    $fallback    optional  whether to allow fallback to default connection if named connection does not exist
     * @param  string  $descriptor  optional  descriptor for database initializer
     */
    public function __construct($fallback = true, $descriptor = null)
    {
        $this->fallback   = $fallback;
        $this->descriptor = $descriptor;
    }

    /**
     * static constructor
     *
     * @param   bool    $fallback    whether to allow fallback to default connection if named connection does not exist
     * @param   string  $descriptor  descriptor for database initializer
     * @return  DatabaseBindingModule
     */
    public static function create($fallback = true, $descriptor = null)
    {
        return new self($fallback, $descriptor);
    }

    /**
     * sets name of database initializer class to bind
     *
     * @param   string  $configReaderClass
     * @return  DatabaseBindingModule
     */
    public function setConfigReaderClass($configReaderClass)
    {
        $this->configReaderClass = $configReaderClass;
        return $this;
    }

    /**
     * configure the binder
     *
     * @param  Binder  $binder
     */
    public function configure(Binder $binder)
    {
        $binder->bind('net\stubbles\db\config\DatabaseConfigReader')
               ->to($this->configReaderClass);
        $binder->bindConstant('net.stubbles.db.fallback')
               ->to($this->fallback);
        if (null !== $this->descriptor) {
            $binder->bindConstant('net.stubbles.db.descriptor')
                   ->to($this->descriptor);
        }
    }
}
