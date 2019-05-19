<?php

namespace Drupal\tns_gallup\Tests;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Test basic functionality of TNS Gallup module.
 *
 * @group TnsGallup
 */
class BasicTest extends UnitTestCase {

  const EXCLUDE_PAGES = 0;
  const INCLUDE_PAGES = 1;
  const TNS_GALLUP_NOT_INCLUDED = FALSE;
  const TNS_GALLUP_INCLUDED = TRUE;

  /**
   * Configuration of the module.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|ImmutableConfig
   */
  protected $config;

  /**
   * Factory for retrieving configuration.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user to simulate during the test.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|AccountInterface
   */
  protected $user;

  /**
   * The module handler for invoking alters.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The url generator.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The alias manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathMatcher;

  /**
   * The route match representing the route being accessed during the test.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeMatch;

  /**
   * The Drupal renderer.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Setup mock services in container.
    $container = new ContainerBuilder();

    // Setup mock configuration.
    $this->config = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->configFactory = $this->getMock(ConfigFactoryInterface::class);
    $this->configFactory
      ->method('get')
      ->with('tns_gallup.settings')
      ->willReturn($this->config);
    $container->set('config.factory', $this->configFactory);

    // Mock user will always return an empty set of roles. Role information is
    // not relevant for most tests.
    $this->user = $this->getMock(AccountInterface::class);
    $this->user
      ->method('getRoles')
      ->willReturn([]);
    $container->set('current_user', $this->user);

    // The rest of the services merely need to be defined for now. They have no
    // default behavior.
    $this->moduleHandler = $this->getMock(ModuleHandlerInterface::class);
    $container->set('module_handler', $this->moduleHandler);

    $this->urlGenerator = $this->getMock(UrlGeneratorInterface::class);
    $container->set('url_generator', $this->urlGenerator);

    $this->aliasManager = $this->getMock(AliasManagerInterface::class);
    $container->set('path.alias_manager', $this->aliasManager);

    $this->pathMatcher = $this->getMock(PathMatcherInterface::class);
    $container->set('path.matcher', $this->pathMatcher);

    $this->routeMatch = $this->getMock(RouteMatchInterface::class);
    $container->set('current_route_match', $this->routeMatch);

    $this->renderer = $this->getMock(RendererInterface::class);
    $container->set('renderer', $this->renderer);

    \Drupal::setContainer($container);

    // Load our module file to be able to call the function we want to test.
    require_once __DIR__ . '/../../tns_gallup.module';
  }

  /**
   * Test inclusion of TNS gallup element based on site id.
   *
   * @param string $site_id
   *   The TNS Gallup site id.
   * @param bool $tns_gallup_included
   *   Whether the TNS gallup element should be included for the site id or not.
   *
   * @dataProvider providerSiteId
   */
  public function testSiteId($site_id, $tns_gallup_included) {
    $this->config
      ->method('get')
      ->willReturnMap([
        ['site_id', $site_id],
        // Default configuration will include TNS gallup on all pages.
        ['visibility', self::EXCLUDE_PAGES],
        ['pages', ''],
        ['roles', []],
      ]);

    $variables = [
      'page_bottom' => [],
    ];
    tns_gallup_preprocess_html($variables);

    if ($tns_gallup_included) {
      $this->assertTnsGallupIncluded($variables);
    }
    else {
      $this->assertTnsGallupNotIncluded($variables);
    }
  }

  /**
   * Data provider for tests of site id.
   *
   * @return array
   *   A combination of site id and TNS Gallup inclusion expectation.
   */
  public function providerSiteId() {
    return [
      [NULL, self::TNS_GALLUP_NOT_INCLUDED],
      ['site_id', self::TNS_GALLUP_INCLUDED],
    ];

  }

  /**
   * Test inclusion of TNS Gallup element based on paths and visibility setting.
   *
   * @param int $include_exclude
   *   Whether the element should be excluded (0) or included (1) for the
   *   defined pages.
   * @param string $pages
   *   The pages to include or exclude the element on. This can be aliases or
   *   internal paths.
   * @param string $current_path
   *   The internal path to test for.
   * @param string $current_alias
   *   The alias to test for.
   * @param bool $tns_gallup_included
   *   Whether the TNS gallup element should be included for the path and
   *   visibility setting or not.
   *
   * @dataProvider providerPathVisibility
   */
  public function testPathVisibility($include_exclude, $pages, $current_path, $current_alias, $tns_gallup_included) {
    $this->config
      ->method('get')
      ->willReturnMap(
        [
          ['site_id', 'testid'],
          ['visibility', $include_exclude],
          ['pages', $pages],
          ['roles', []],
        ]
      );

    // Use the specified path as the current path.
    $route = $this->getMockBuilder(Route::class)
      ->disableOriginalConstructor()
      ->getMock();
    $route
      ->expects($this->atLeastOnce())
      ->method('getPath')
      ->willReturn($current_path);
    $this->routeMatch
      ->method('getRouteObject')
      ->willReturn($route);

    // Set the specified alias as the alias for the current path.
    $this->aliasManager
      ->expects($this->atLeastOnce())
      ->method('getAliasByPath')
      ->willReturn($current_alias);

    // Check whether the specified pages match the specified path or alias.
    $this->pathMatcher
      ->expects($this->atLeastOnce())
      ->method('matchPath')
      ->willReturnCallback(function ($path, $patterns) {
        // A simple equality check is fine for us.
        return $path == $patterns;
      });

    $variables = [
      'page_bottom' => [],
    ];
    tns_gallup_preprocess_html($variables);

    if ($tns_gallup_included) {
      $this->assertTnsGallupIncluded($variables);
    }
    else {
      $this->assertTnsGallupNotIncluded($variables);
    }
  }

  /**
   * Dataprovider for testPathVisibility().
   *
   * @return array
   *   A combination of path and alias configuration with TNS Gallup inclusion
   *   expectation.
   */
  public function providerPathVisibility() {
    return [
      [
        self::EXCLUDE_PAGES,
        'pages',
        'internal-path',
        'alias',
        self::TNS_GALLUP_INCLUDED,
      ],
      [
        self::EXCLUDE_PAGES,
        'internal-path',
        'internal-path',
        'alias',
        self::TNS_GALLUP_NOT_INCLUDED,
      ],
      [
        self::EXCLUDE_PAGES,
        'alias',
        'internal-path',
        'alias',
        self::TNS_GALLUP_NOT_INCLUDED,
      ],
      [
        self::INCLUDE_PAGES,
        'pages',
        'internal-path',
        'alias',
        self::TNS_GALLUP_NOT_INCLUDED,
      ],
      [
        self::INCLUDE_PAGES,
        'internal-path',
        'internal-path',
        'alias',
        self::TNS_GALLUP_INCLUDED,
      ],
      [
        self::INCLUDE_PAGES,
        'alias',
        'internal-path',
        'alias',
        self::TNS_GALLUP_INCLUDED,
      ],
    ];
  }

  /**
   * The inclusion of TNS Gallup element based on configured and user roles.
   *
   * @param array $enabled_roles
   *   An map of roles. Keys are role name and values are the role names or 0
   *   if the role is disabled.
   * @param array $user_roles
   *   An array of user roles.
   * @param bool $tns_gallup_included
   *   Whether the TNS gallup element should be included for the configured and
   *   user roles or not.
   *
   * @dataProvider providerRoleVisibility
   */
  public function testRoleVisibility(array $enabled_roles, array $user_roles, $tns_gallup_included) {
    $this->config
      ->method('get')
      ->willReturnMap(
        [
          ['site_id', 'testid'],
          ['visibility', self::EXCLUDE_PAGES],
          ['pages', ''],
          ['roles', $enabled_roles],
        ]
      );

    // We have to reset the current user in the container to have a new mock
    // which actually return roles.
    $this->user = $this->getMock(AccountInterface::class);
    $this->user
      ->method('getRoles')
      ->willReturn($user_roles);
    \Drupal::getContainer()->set('current_user', $this->user);

    $variables = [
      'page_bottom' => [],
    ];
    tns_gallup_preprocess_html($variables);

    if ($tns_gallup_included) {
      $this->assertTnsGallupIncluded($variables);
    }
    else {
      $this->assertTnsGallupNotIncluded($variables);
    }
  }

  /**
   * Dataprovider for testRoleVisibility().
   *
   * @return array
   *   A combination of user role and role configuration with TNS Gallup
   *   inclusion expectation.
   */
  public function providerRoleVisibility() {
    return [
      [
        ['role a' => 'role a', 'role b' => 0],
        ['role a'],
        self::TNS_GALLUP_INCLUDED,
      ],
      [
        ['role a' => 'role a', 'role b' => 0],
        ['role c'],
        self::TNS_GALLUP_NOT_INCLUDED,
      ],
    ];
  }

  /**
   * Test that the TNS Gallup element is not included in a variables array.
   *
   * @param array $variables
   *   A variables array as passed to a hook_preprocess() implementation.
   */
  protected function assertTnsGallupNotIncluded(array $variables) {
    return $this->assertArrayNotHasKey('tns_gallup', $variables['page_bottom']);
  }

  /**
   * Test that the TNS Gallup element is included in a variables array.
   *
   * @param array $variables
   *   A variables array as passed to a hook_preprocess() implementation.
   */
  protected function assertTnsGallupIncluded(array $variables) {
    $this->assertArrayHasKey('tns_gallup', $variables['page_bottom']);
  }

}
