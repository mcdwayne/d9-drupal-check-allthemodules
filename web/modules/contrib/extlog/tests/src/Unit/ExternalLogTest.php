<?php

namespace Drupal\Tests\extlog\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\extlog\Logger\ExternalLog;

/**
 * Simple test to ensure that asserts pass.
 *
 * @group extlog
 */
class ExternalLogTest extends UnitTestCase
{
    /**
     * Assert instance of ExternalLog can be created.
     *
     * @return void
     */
    public function testCanCreateInstance()
    {
        $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
        $parser = $this->getMock('Drupal\Core\Logger\LogMessageParserInterface');

        $extLog = new ExternalLog($config_factory, $parser);

        $this->assertTrue($extLog instanceof ExternalLog);
    }

    /**
     * Assert log() method returns void.
     *
     * @covers Drupal\extlog\Logger\ExternalLog::log
     * @return void
     */
    public function testLog()
    {
        // set get('active') to return true
        $configMock = $this->getMockBuilder('anyClass')
            ->setMethods([
                'get'
            ])->getMock();
        $configMock->method('get')->willReturn(true);

        $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
        $config_factory->method('get')->willReturn($configMock);

        $parser = $this->getMock('Drupal\Core\Logger\LogMessageParserInterface');

        $extLog = new ExternalLog($config_factory, $parser);

        $this->assertNull($extLog->log('', ''));
    }
}
