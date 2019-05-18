<?php

namespace Drupal\Tests\monster_menus\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\monster_menus\Controller\MMTreeBrowserController;

/**
 * @coversDefaultClass \Drupal\monster_menus\Controller\MMTreeBrowserController
 * @group monster_menus
 */
class MMTreeBrowserControllerTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->database = $this->getMockBuilder('\Drupal\Core\Database\Connection')
                            ->disableOriginalConstructor()
                            ->getMock();
  }

  /**
   * Test the static create method.
   *
   */
  public function testCreate() {

    $request_stack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($this->getMock('Symfony\Component\HttpFoundation\Request'));

    $renderer = $this->getMockBuilder('Drupal\Core\Render\Renderer')
      ->disableOriginalConstructor()
      ->getMock();

    $plugin_manager = $this->getMock('Drupal\Component\Plugin\PluginManagerInterface');

    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('get')
      ->will($this->onConsecutiveCalls($this->database, $request_stack, $renderer, $plugin_manager));

    $this->assertInstanceOf('\Drupal\monster_menus\Controller\MMTreeBrowserController', MMTreeBrowserController::create($container));
  }
}