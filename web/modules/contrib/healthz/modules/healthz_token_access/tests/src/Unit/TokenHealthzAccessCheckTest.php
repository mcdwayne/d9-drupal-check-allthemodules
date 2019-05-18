<?php

namespace Drupal\Tests\healthz_token_access\Unit;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatch;
use Drupal\healthz_token_access\TokenHealthzAccessCheck;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Unit tests for the TokenHealthzAccessCheck class.
 *
 * @coversDefaultClass \Drupal\healthz_token_access\TokenHealthzAccessCheck
 *
 * @group healthz_token_access
 */
class TokenHealthzAccessCheckTest extends UnitTestCase {


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Initialize Drupal container since the cache context manager is needed.
    $contexts_manager = $this->prophesize(CacheContextsManager::class);
    $contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $builder = new ContainerBuilder();
    $builder->set('cache_contexts_manager', $contexts_manager->reveal());
    \Drupal::setContainer($builder);
  }

  /**
   * Tests the access method.
   *
   * @covers ::access
   *
   * @dataProvider tokenAccessCheckProvider
   */
  public function testTokenAccessCheck($access_token, $query, $access_result) {
    $config_object = $this->prophesize(ImmutableConfig::class);
    $config_object->get('access_token')->willReturn($access_token);
    $config_object->getCacheContexts()->willReturn([]);
    $config_object->getCacheTags()->willReturn([]);
    $config_object->getCacheMaxAge()->willReturn(-1);
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('healthz_token_access.settings')->willReturn($config_object);

    $request = new Request($query);
    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack->getCurrentRequest()->willReturn($request);

    $access = new TokenHealthzAccessCheck($config_factory->reveal(), $request_stack->reveal());
    $this->assertInstanceOf($access_result, $access->access($this->prophesize(Route::class)->reveal(), $this->prophesize(RouteMatch::class)->reveal()));
  }

  /**
   * Data provider for testTokenAccessCheck().
   */
  public function tokenAccessCheckProvider() {
    return [
      [NULL, [], AccessResultAllowed::class],
      ['foo', ['token' => 'foo'], AccessResultAllowed::class],
      ['', ['token' => ''], AccessResultAllowed::class],
      [NULL, ['token' => ''], AccessResultNeutral::class],
      ['foo', ['token' => 'bar'], AccessResultNeutral::class],
      ['foo', [], AccessResultNeutral::class],
    ];
  }

}
