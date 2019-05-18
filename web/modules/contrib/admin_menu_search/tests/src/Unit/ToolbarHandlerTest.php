<?php

namespace Drupal\Tests\admin_menu_search\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Unit test for ToolbarHandler.
 */
class ToolbarHandlerTest extends UnitTestCase {

  /**
   * Unit test setup.
   */
  protected function setUp() {
    parent::setUp();
    $this->toolbar_handler = $this->getMockBuilder('Drupal\admin_menu_search\ToolbarHandler')
      ->disableOriginalConstructor()
      ->setMethods(['checkAccess', 'getMenuSearchForm', 't'])
      ->getMock();
    $this->toolbar_handler->expects($this->any())
      ->method('t')
      ->will($this->returnCallback([$this, 'returnCallbackT']));
  }

  /**
   * Custom return callback for t().
   *
   * @return string
   *   String
   */
  public function returnCallbackT() {
    $args = func_get_args();

    return $args[0];
  }

  /**
   * Test toolbar menu search for user who has access.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\ToolbarHandler
   */
  public function testToolbarHandlerWithAccess() {
    $expected_value = [
      'admin_menu_search' => [
        '#cache' => [
          'contexts' => ['user.permissions'],
        ],
      ]
    ];
    $this->toolbar_handler->expects($this->exactly(1))
      ->method('checkAccess')
      ->willReturn(TRUE);
    $actual_value = $this->toolbar_handler->toolbar();
    $this->assertNotEquals($expected_value, $actual_value);
  }

  /**
   * Test toolbar menu search for user who don't has access.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\ToolbarHandler
   */
  public function testToolbarHandlerWithoutAccess() {
    $expected_value = [
      'admin_menu_search' => [
        '#cache' => [
          'contexts' => ['user.permissions'],
        ],
      ]
    ];
    $this->toolbar_handler->expects($this->exactly(1))
      ->method('checkAccess')
      ->willReturn(FALSE);
    $actual_value = $this->toolbar_handler->toolbar();
    $this->assertEquals($expected_value, $actual_value);
  }

}
