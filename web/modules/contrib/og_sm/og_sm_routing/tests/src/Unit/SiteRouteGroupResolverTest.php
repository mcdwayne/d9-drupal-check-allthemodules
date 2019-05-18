<?php

namespace Drupal\Tests\og_sm_routing\Unit;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og_sm_routing\Plugin\OgGroupResolver\SiteRouteGroupResolver;
use Drupal\Tests\og_sm_context\Unit\OgSmGroupResolverTestBase;

/**
 * Tests the SiteRouteGroupResolver plugin.
 *
 * @group og_sm
 * @coversDefaultClass \Drupal\og_sm_routing\Plugin\OgGroupResolver\SiteRouteGroupResolver
 */
class SiteRouteGroupResolverTest extends OgSmGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = SiteRouteGroupResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_sm_context_site_route';

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
  public function testResolve($route_object_id = NULL, $expected_added_group = NULL) {
    if ($route_object_id) {
      $this->routeMatch->getParameter('og_sm_routing:site')
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
        NULL,
        NULL,
      ],
      [
        'group',
        NULL,
      ],
      [
        'site',
        'site',
      ],
    ];
  }

}
