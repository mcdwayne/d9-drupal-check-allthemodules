<?php

namespace Drupal\Tests\admin_menu_search\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit test for AutoCompleteController.
 */
class AutoCompleteControllerTest extends UnitTestCase {

  /**
   * Unit test setup.
   */
  protected function setUp() {
    parent::setUp();
    $this->controller = $this->getMockBuilder('Drupal\admin_menu_search\Controller\AutoCompleteController')
      ->setMethods(['getAdminMenuTreeIndex', 'getMenuUrl'])
      ->disableOriginalConstructor()
      ->getMock();
    $this->controller->expects($this->any())
      ->method('getMenuUrl')
      ->will($this->returnCallback([$this, 'returnCallbackMenuUrl']));
  }

  /**
   * Custom return callback for getMenuUrl().
   *
   * @return string
   *   String
   */
  public function returnCallbackMenuUrl() {
    $args = func_get_args();

    return '/' . str_replace('.', '/', $args[0]);
  }

  /**
   * Test empty keyword.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\Controller\AutoCompleteController
   */
  public function testEmptyKeyword() {
    $request = new Request(['q' => '']);
    $actual_value = $this->controller->autocomplete($request);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $actual_value);
    $this->assertEquals('[]', $actual_value->getContent());
  }

  /**
   * Test non-empty keyword.
   *
   * @group admin_menu_search
   *
   * @covers Drupal\admin_menu_search\Controller\AutoCompleteController
   */
  public function testNonEmptyKeyword() {
    $request = new Request(['q' => 'test']);
    $this->controller->expects($this->exactly(1))
      ->method('getAdminMenuTreeIndex')
      ->willReturn([
        [
          'title' => '',
          'name' => '',
          'parameters' => [],
        ],
        [
          'title' => 'Menu test',
          'name' => 'menu.test',
          'parameters' => [],
        ],
        [
          'title' => 'Test menu',
          'name' => 'test.menu',
          'parameters' => [],
        ],
        [
          'title' => 'Example menu',
          'name' => 'example.menu',
          'parameters' => [],
        ],
      ]);
    $actual_value = $this->controller->autocomplete($request);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $actual_value);
    $expected_value = '[{"href":"\/test\/menu","value":"Test menu","label":"Test menu"},{"href":"\/menu\/test","value":"Menu test","label":"Menu test"}]';
    $this->assertEquals($expected_value, $actual_value->getContent());
  }

}
