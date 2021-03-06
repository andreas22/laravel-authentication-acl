<?php  namespace Jacopo\Authentication\Tests;

/**
 * Test SentryMenuFactoryTest
 *
 * @author jacopo beschi jacopo@jacopobeschi.com
 */
use Jacopo\Authentication\Classes\Menu\SentryMenuFactory;
use \Config;
use Mockery as m;

class SentryMenuFactoryTest extends TestCase
{

    public function tearDown()
    {
        m::close();
    }

    /**
     * @test
     **/
    public function it_creates_a_collection()
    {
        $this->initializeConfig();

        $collection = SentryMenuFactory::create();

        $this->assertInstanceOf('Jacopo\Authentication\Classes\Menu\MenuItemCollection', $collection);
        $items = $collection->getItemList();
        $this->assertEquals(2, count($items));
        $this->assertEquals("name1", $items[0]->getName());
        $this->assertEquals("route2", $items[1]->getRoute());
    }

    /**
     * @test
     **/
    public function it_create_collection_checking_permissions()
    {
        $this->initializeConfig();
        $this->mockSentryHasAccessOnlyOnFirstItem();

        $collection = SentryMenuFactory::create();

        $this->assertInstanceOf('Jacopo\Authentication\Classes\Menu\MenuItemCollection', $collection);
        $items = $collection->getItemListAvailable();
        $this->assertEquals(1, count($items));
        $this->assertEquals("name1", $items[0]->getName());
        $this->assertEquals("route1", $items[0]->getRoute());
    }

    private function initializeConfig()
    {
        $config_arr = [
                [
                        "name"        => "name1",
                        "link"        => "link1",
                        "permissions" => ["permission1"],
                        "route"       => "route1"
                ],
                [
                        "name"        => "name1",
                        "link"        => "link1",
                        "permissions" => ["permission1"],
                        "route"       => "route2"
                ]
        ];
        Config::set(SentryMenuFactory::$config_file, $config_arr);
    }

    private function mockSentryHasAccessOnlyOnFirstItem()
    {
        $mock_sentry = m::mock('StdClass')->shouldReceive('hasAnyAccess')->andReturn(true, false)->getMock();
        $mock_current = m::mock('StdClass')->shouldReceive('getUser')->andReturn($mock_sentry)->getMock();
        \App::instance('sentry', $mock_current);
    }
}
 