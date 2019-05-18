<?php

namespace Drupal\Tests\monster_menus\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\monster_menus\Controller\MMTreeViewController;

/**
 * @coversDefaultClass \Drupal\monster_menus\Controller\MMTreeViewController
 * @group monster_menus
 */
class MMTreeViewControllerTest extends UnitTestCase {

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
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('get')
      ->will($this->onConsecutiveCalls($this->database));

    $this->assertInstanceOf('\Drupal\monster_menus\Controller\MMTreeViewController', MMTreeViewController::create($container));
  }

}