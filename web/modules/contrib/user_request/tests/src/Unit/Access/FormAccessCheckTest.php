<?php

namespace Drupal\Tests\user_request\Unit\Access;

/**
 * Base class for form access check tests.
 *
 * @group user_request
 */
abstract class FormAccessCheckTest extends AccessTest {

  /**
   * The class under test.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface
   */
  protected $formAccessCheck;

  /**
   * A mocked route.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;

  protected function setUp() {
    parent::setUp();

    // Mocks a route.
    $this->route = $this->mockRoute();
  }

  protected function createFormAccessCheck($class) {
    return new $class();
  }

  protected function mockRoute() {
    return $this->getMockBuilder('\Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();
  }

  protected function mockRouteMatch(array $parameters = []) {
    $route_match = $this->getMock('\Drupal\Core\Routing\RouteMatchInterface');
    $route_match
      ->expects($this->any())
      ->method('getParameter')
      ->will($this->returnValueMap(
        array_map(NULL, array_keys($parameters), array_values($parameters))));
    return $route_match;
  }

}
