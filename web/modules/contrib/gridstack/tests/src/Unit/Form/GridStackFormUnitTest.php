<?php

namespace Drupal\Tests\gridstack\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Messenger\Messenger;
use Drupal\gridstack_ui\Form\GridStackForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the GridStack admin form.
 *
 * @coversDefaultClass \Drupal\gridstack_ui\Form\GridStackForm
 * @group gridstack
 */
class GridStackFormUnitTest extends UnitTestCase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fileSystem = $this->getMockBuilder('\Drupal\Core\File\FileSystem')
      ->disableOriginalConstructor()
      ->getMock();
    $this->messenger = $this->getMockBuilder('\Drupal\Core\Messenger\Messenger')
      ->disableOriginalConstructor()
      ->getMock();
    $this->blazyAdmin = $this->getMockBuilder('\Drupal\blazy\Form\BlazyAdminInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->gridstackManager = $this->createMock('\Drupal\gridstack\GridStackManagerInterface');
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testBlazyAdminCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['file_system', $exception, $this->fileSystem],
      ['messenger', $exception, $this->messenger],
      ['blazy.admin', $exception, $this->blazyAdmin],
      ['gridstack.manager', $exception, $this->gridstackManager],
    ];

    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $gridstackForm = GridStackForm::create($container);
    $this->assertInstanceOf(GridStackForm::class, $gridstackForm);
  }

}
