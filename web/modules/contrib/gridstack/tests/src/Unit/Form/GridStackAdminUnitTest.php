<?php

namespace Drupal\Tests\gridstack\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\gridstack\Form\GridStackAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the GridStack admin form.
 *
 * @coversDefaultClass \Drupal\gridstack\Form\GridStackAdmin
 * @group gridstack
 */
class GridStackAdminUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->blazyAdminExtended = $this->getMockBuilder('\Drupal\blazy\Dejavu\BlazyAdminExtended')
      ->disableOriginalConstructor()
      ->getMock();
    $this->gridstackManager = $this->createMock('\Drupal\gridstack\GridStackManagerInterface');
  }

  /**
   * @covers ::create
   * @covers ::__construct
   * @covers ::blazyAdmin
   * @covers ::manager
   */
  public function testBlazyAdminCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['blazy.admin.extended', $exception, $this->blazyAdminExtended],
      ['gridstack.manager', $exception, $this->gridstackManager],
    ];

    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $gridstackAdmin = GridStackAdmin::create($container);
    $this->assertInstanceOf(GridStackAdmin::class, $gridstackAdmin);

    $this->assertInstanceOf('\Drupal\blazy\Dejavu\BlazyAdminExtended', $gridstackAdmin->blazyAdmin());
    $this->assertInstanceOf('\Drupal\gridstack\GridStackManagerInterface', $gridstackAdmin->manager());
  }

}
