<?php

namespace Drupal\Tests\og_sm_routing\Kernel;

use Drupal\node\NodeInterface;
use Drupal\og_sm\OgSm;
use Drupal\Tests\og_sm\Functional\OgSmWebTestBase;
use Drupal\Tests\og_sm\Kernel\OgSmKernelTestBase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Tests the site routing events.
 *
 * @group og_sm
 */
class SiteRoutingTest extends OgSmKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'og_sm_routing',
    'og_sm_routing_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Test the site route collection.
   */
  public function testSiteRouteCollection() {
    $route_name = 'og_sm_routing_test.published';

    // Create 2 sites.
    $site_type = $this->createGroupNodeType(OgSmWebTestBase::TYPE_IS_GROUP);
    OgSm::siteTypeManager()->setIsSiteType($site_type, TRUE);
    $site_type->save();
    $site1 = $this->createGroup($site_type->id());
    $site2 = $this->createGroup($site_type->id());

    $this->assertSiteRouteExists($route_name, $site1, 'The published route exists for site 1.');
    $this->assertSiteRouteExists($route_name, $site2, 'The published route exists for site 2.');

    // When a site is unpublished the published page should not be available.
    $site2->setUnpublished()->save();
    $this->assertSiteRouteNotExists($route_name, $site2, 'The published route does not exist for site 2 after unpublishing.');

    // The published page should be created when creating a new site.
    $site3 = $this->createGroup($site_type->id());
    $this->assertSiteRouteExists($route_name, $site3, 'The published route exists for site 3.');
    // Deleting the site should also remove the published path.
    $site3->delete();
    $this->assertSiteRouteNotExists($route_name, $site3, 'The published route does not exist anymore once site 3 has been deleted.');

    // Verify that the SiteRoutingEvents::ALTER event changed the path to end.
    $route = $this->getSiteRoute($route_name, $site1);
    $this->assertEquals('/group/node/' . $site1->id() . '/is-published', $route->getPath());
  }

  /**
   * Helper function that prefixes the site route name with the site id.
   *
   * @param string $route_name
   *   The site route name.
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return string
   *   The actual route name.
   */
  protected function getActualRouteName($route_name, $site) {
    return 'og_sm_site:' . $site->id() . ':' . $route_name;
  }

  /**
   * Gets a site route.
   *
   * @param string $route_name
   *   The site route name.
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return \Symfony\Component\Routing\Route
   *   The site route.
   */
  protected function getSiteRoute($route_name, $site) {
    /* @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = $this->container->get('router.route_provider');
    $route_name = $this->getActualRouteName($route_name, $site);
    return $route_provider->getRouteByName($route_name);
  }

  /**
   * Asserts that a site route exists.
   *
   * @param string $route_name
   *   The site route name.
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param string $message
   *   Additional information about the test.
   */
  protected function assertSiteRouteExists($route_name, $site, $message = '') {
    $route = $this->getSiteRoute($route_name, $site);
    $this->assertNotEmpty($route, $message);
  }

  /**
   * Asserts that a site route doesn't exists.
   *
   * @param string $route_name
   *   The site route name.
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param string $message
   *   Additional information about the test.
   */
  protected function assertSiteRouteNotExists($route_name, NodeInterface $site, $message = '') {
    try {
      $this->getSiteRoute($route_name, $site);
    }
    catch (RouteNotFoundException $exception) {
      $this->assertTrue(TRUE, $message);
      return;
    }
    $this->assertTrue(FALSE, $message);
  }

}
