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
use stubbles\ioc\Binder;
/**
 * Test for net\stubbles\db\ioc\DatabaseBindingModule.
 *
 * @group  db
 * @group  ioc
 */
class DatabaseBindingModuleTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  DatabaseBindingModule
     */
    private $databaseBindingModule;
    /**
     *
     * @type  Binder
     */
    private $binder;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->binder                = new Binder();
        $this->databaseBindingModule = new DatabaseBindingModule();
    }

    /**
     * @test
     */
    public function usesProperyBasedConfigReaderByDefault()
    {
        $this->databaseBindingModule->configure($this->binder);
        $this->binder->bindConstant('stubbles.config.path')->to(__DIR__);
        $this->assertInstanceOf('net\stubbles\db\config\PropertyBasedDatabaseConfigReader',
                                $this->binder->getInjector()
                                             ->getInstance('net\stubbles\db\config\DatabaseConfigReader')
        );
    }

    /**
     * @test
     */
    public function configReaderBindingCanBeChanged()
    {
        $mockClass = get_class($this->getMock('net\stubbles\db\config\DatabaseConfigReader'));
        $this->databaseBindingModule->setConfigReaderClass($mockClass)
                                    ->configure($this->binder);
        $this->assertInstanceOf($mockClass,
                                $this->binder->getInjector()
                                             ->getInstance('net\stubbles\db\config\DatabaseConfigReader')
        );
    }

    /**
     * @test
     */
    public function fallbackIsEnabledByDefault()
    {
        $this->databaseBindingModule->configure($this->binder);
        $this->assertTrue($this->binder->getInjector()
                                       ->getConstant('stubbles.db.fallback')
        );
    }

    /**
     * @test
     */
    public function fallbackCanBeDisabled()
    {
        DatabaseBindingModule::create(false)->configure($this->binder);
        $this->assertFalse($this->binder->getInjector()
                                        ->getConstant('stubbles.db.fallback')
        );
    }

    /**
     * @test
     */
    public function descriptorNotBoundByDefault()
    {
        $this->databaseBindingModule->configure($this->binder);
        $this->assertFalse($this->binder->hasConstant('stubbles.db.descriptor'));
    }

    /**
     * @test
     */
    public function descriptorCanBeBound()
    {
        DatabaseBindingModule::create(true, 'rdbms-test')->configure($this->binder);
        $this->assertEquals('rdbms-test',
                            $this->binder->getInjector()
                                        ->getConstant('stubbles.db.descriptor')
        );
    }

}