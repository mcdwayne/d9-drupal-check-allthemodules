<?php

namespace Drupal\Tests\og_sm_context\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og_sm_context\Plugin\OgGroupResolver\GroupPathGroupResolver;
use Symfony\Component\Routing\Route;

/**
 * Tests the GroupPathGroupResolver plugin.
 *
 * @group og_sm
 * @coversDefaultClass \Drupal\og_sm_context\Plugin\OgGroupResolver\GroupPathGroupResolver
 */
class GroupPathGroupResolverTest extends OgSmGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = GroupPathGroupResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_sm_context_group_path';

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $path
   *   The current path.
   * @param string $route_object_id
   *   The ID of the object passed in the path.
   * @param string $expected_added_group
   *   The group that is expected to be added by the plugin. If left empty it is
   *   explicitly expected that the plugin will not add any group to the
   *   collection.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($path = NULL, $route_object_id = NULL, $expected_added_group = NULL) {
    if ($path) {
      /** @var \Symfony\Component\Routing\Route|\Prophecy\Prophecy\ObjectProphecy $route */
      $route = $this->prophesize(Route::class);
      $route
        ->getPath()
        ->willReturn($path)
        ->shouldBeCalled();
      $this->routeMatch
        ->getRouteObject()
        ->willReturn($route->reveal())
        ->shouldBeCalled();
    }

    if ($route_object_id) {
      $this->routeMatch->getParameter('node')
        ->willReturn($this->testEntities[$route_object_id]);
    }

    $this->mightRetrieveSite($expected_added_group);
  }

  /**
   * {@inheritdoc}
   */
  protected function getInjectedDependencies() {
    return [
      $this->routeMatch->reveal(),
      $this->siteManager->reveal(),
    ];
  }

  /**
   * Data provider for testResolve().
   *
   * @see ::testResolve()
   */
  public function resolveProvider() {
    return [
      [
        '/user/logout',
        NULL,
        NULL,
      ],
      [
        '/group/node/{node}/admin',
        'group',
        NULL,
      ],
      [
        '/group/node/{node}/admin',
        'site',
        'site',
      ],
      [
        '/group/node/{node}/admin/config',
        'site',
        'site',
      ],
      [
        '/group/node/{node}/admin',
        'site_content',
        NULL,
      ],
      [
        '/group/node/{node}/admin/config',
        'site_content',
        NULL,
      ],
      [
        '/group/node/{node}/admin',
        'non_group',
        NULL,
      ],
    ];
  }

}
