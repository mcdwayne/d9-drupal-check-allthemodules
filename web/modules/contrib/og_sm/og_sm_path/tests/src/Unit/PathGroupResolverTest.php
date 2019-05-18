<?php

namespace Drupal\Tests\og_sm_path\Unit;

use Drupal\og_sm_path\Plugin\OgGroupResolver\PathGroupResolver;
use Drupal\og_sm_path\SitePathManagerInterface;
use Drupal\Tests\og_sm_context\Unit\OgSmGroupResolverTestBase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the PathGroupResolver plugin.
 *
 * @group og_sm
 * @coversDefaultClass \Drupal\og_sm_path\Plugin\OgGroupResolver\PathGroupResolver
 */
class PathGroupResolverTest extends OgSmGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = PathGroupResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_sm_context_path';

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $request;

  /**
   * The site path manager.
   *
   * @var \Drupal\og_sm_path\SitePathManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sitePathManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->request = $this->prophesize(Request::class);
    $this->sitePathManager = $this->prophesize(SitePathManagerInterface::class);

    $test_entity_properties = $this->getTestEntityProperties();
    foreach ($this->getPathAliases() as $source => $alias) {
      $parts = explode('/', $source);
      $id = $parts[2];
      $properties = $test_entity_properties[$id];
      if (!empty($properties['site'])) {
        $this->sitePathManager->getSiteFromPath($alias)->willReturn($this->testEntities[$id]);
      }
      else {
        $this->sitePathManager->getSiteFromPath($alias)->willReturn(FALSE);
      }

      $this->sitePathManager->lookupPathAlias($source)->willReturn($alias);
      $this->sitePathManager->lookupPathAlias($alias)->willReturn($alias);
    }
    $this->sitePathManager->lookupPathAlias(Argument::any())->willReturnArgument(0);
    $this->sitePathManager->getSiteFromPath(Argument::any())->willReturn(FALSE);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $path
   *   The current path.
   * @param string $expected_added_group
   *   The group that is expected to be added by the plugin. If left empty it is
   *   explicitly expected that the plugin will not add any group to the
   *   collection.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($path = NULL, $expected_added_group = NULL) {
    $this->request->getPathInfo()->willReturn($path);
    $this->mightRetrieveSite($expected_added_group);
  }

  /**
   * {@inheritdoc}
   */
  protected function getInjectedDependencies() {
    return [
      $this->request->reveal(),
      $this->sitePathManager->reveal(),
    ];
  }

  /**
   * Gets an array of path aliases for the test entities.
   *
   * @return array
   *   An array of path aliases, keyed by path source, value is the path alias.
   */
  protected function getPathAliases() {
    return [
      '/node/group' => '/group',
      '/node/site' => '/site',
      '/node/site_content' => '/site/site-content',
      '/node/non_group' => '/non-group',
      '/node/group_content' => '/group-content',
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
        '/node/non_group',
        NULL,
      ],
      [
        '/node/group',
        NULL,
      ],
      [
        '/node/group_content',
        NULL,
      ],
      [
        '/node/site',
        'site',
      ],
      [
        '/node/site_content',
        'site',
      ],
      [
        '/site/site-content',
        'site',
      ],
      [
        '/whatever/foo/bar/biz/baz',
        NULL,
      ],
      [
        '/site/whatever/foo/bar/biz/baz',
        'site',
      ],
    ];
  }

}
