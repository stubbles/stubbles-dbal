<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\db
 */
namespace stubbles\db\ioc;
use stubbles\db\config\DatabaseConfiguration;
/**
 * Test for stubbles\db\ioc\ConnectionProvider.
 *
 * @group  db
 * @group  ioc
 */
class ConnectionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ConnectionProvider
     */
    private $connectionProvider;
    /**
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockConfigReader;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockConfigReader   = $this->getMock('stubbles\db\config\DatabaseConfigReader');
        $this->connectionProvider = new ConnectionProvider($this->mockConfigReader);
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\ConfigurationException
     * @expectedExceptionMessage  No database configuration known for database requested with id foo
     */
    public function throwsConfigurationExceptionWhenNoConfigForRequestedDatabaseAvailable()
    {
        $this->mockConfigReader->expects($this->once())
                               ->method('hasConfig')
                               ->with($this->equalTo('foo'))
                               ->will($this->returnValue(false));
        $this->connectionProvider->get('foo');
    }

    /**
     * @test
     */
    public function returnsConnectionForRequestedDatabase()
    {
        $this->mockConfigReader->expects($this->once())
                               ->method('hasConfig')
                               ->with($this->equalTo('foo'))
                               ->will($this->returnValue(true));
        $this->mockConfigReader->expects($this->once())
                               ->method('readConfig')
                               ->with($this->equalTo('foo'))
                               ->will($this->returnValue(new DatabaseConfiguration('foo', 'dsn:bar')));
        $this->assertInstanceOf('stubbles\db\DatabaseConnection',
                                $this->connectionProvider->get('foo')
        );
    }

    /**
     * @test
     */
    public function usesDefaultConnectionWhenNoNameGiven()
    {
        $this->mockConfigReader->expects($this->once())
                               ->method('hasConfig')
                               ->with($this->equalTo(DatabaseConfiguration::DEFAULT_ID))
                               ->will($this->returnValue(true));
        $this->mockConfigReader->expects($this->once())
                               ->method('readConfig')
                               ->with($this->equalTo(DatabaseConfiguration::DEFAULT_ID))
                               ->will($this->returnValue(new DatabaseConfiguration(DatabaseConfiguration::DEFAULT_ID, 'dsn:bar')));
        $this->assertInstanceOf('stubbles\db\DatabaseConnection',
                                $this->connectionProvider->get()
        );
    }

    /**
     * @test
     */
    public function returnsSameInstanceWhenSameNameIsRequestedTwice()
    {
        $this->mockConfigReader->expects($this->once())
                               ->method('hasConfig')
                               ->with($this->equalTo('foo'))
                               ->will($this->returnValue(true));
        $this->mockConfigReader->expects($this->once())
                               ->method('readConfig')
                               ->with($this->equalTo('foo'))
                               ->will($this->returnValue(new DatabaseConfiguration('foo', 'dsn:bar')));
        $this->assertSame($this->connectionProvider->get('foo'),
                          $this->connectionProvider->get('foo')
        );
    }

    /**
     * @test
     * @since  2.1.0
     */
    public function providesListOfAvailableConnectionIds()
    {
        $this->mockConfigReader->expects($this->once())
                               ->method('configIds')
                               ->will($this->returnValue(['default', 'other']));
        $this->assertEquals(['default', 'other'],
                            $this->connectionProvider->availableConnections()
        );
    }
}