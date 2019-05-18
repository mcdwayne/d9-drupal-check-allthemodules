<?php

namespace Drupal\Tests\og_sm_context\Unit;

use Drupal\og_sm_context\Plugin\OgGroupResolver\QueryParamGroupResolver;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the QueryParamGroupResolver plugin.
 *
 * @group og_sm
 * @coversDefaultClass \Drupal\og_sm_context\Plugin\OgGroupResolver\QueryParamGroupResolver
 */
class QueryParamGroupResolverTest extends OgSmGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = QueryParamGroupResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_sm_context_get';

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->request = $this->prophesize(Request::class);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $site_id
   *   The site ID that is passed as a query argument.
   * @param string $expected_added_group
   *   The group that is expected to be added by the plugin. If left empty it is
   *   explicitly expected that the plugin will not add any group to the
   *   collection.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($site_id = NULL, $expected_added_group = NULL) {
    // It will retrieve the query object from the request.
    /** @var \Symfony\Component\HttpFoundation\ParameterBag|\Prophecy\Prophecy\ObjectProphecy $query */
    $query = $this->prophesize(ParameterBag::class);

    // Mock methods to check for the existence and value of the query argument
    // for the site ID. The plugin is allowed to call these.
    $query->has(QueryParamGroupResolver::SITE_ID_ARGUMENT)->willReturn(!empty($site_id));
    $query->get(QueryParamGroupResolver::SITE_ID_ARGUMENT)->willReturn($site_id);

    $this->request->query = $query->reveal();

    $this->mightRetrieveSite($expected_added_group);
  }

  /**
   * {@inheritdoc}
   */
  protected function getInjectedDependencies() {
    return [
      $this->request->reveal(),
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
      [
        'site_content',
        NULL,
      ],
      [
        'non_group',
        NULL,
      ],
    ];
  }

}
