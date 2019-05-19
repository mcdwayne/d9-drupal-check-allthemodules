<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\group\Access\CalculatedGroupPermissions;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the CalculatedGroupPermissions value object.
 *
 * @coversDefaultClass \Drupal\group\Access\CalculatedGroupPermissions
 * @group group
 */
class CalculatedGroupPermissionsTest extends UnitTestCase {

  /**
   * The calculated group permissions object.
   *
   * @var \Drupal\group\Access\CalculatedGroupPermissions
   */
  protected $calculatedPermissions;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->calculatedPermissions = new CalculatedGroupPermissions();
  }

  /**
   * Tests the addition of a calculated permissions item.
   *
   * @covers ::addItem
   * @covers ::getItem
   */
  public function testAddItem() {
    $scope = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE;

    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['bar']);
    $this->calculatedPermissions->addItem($item);
    $this->assertSame($item, $this->calculatedPermissions->getItem($scope, 'foo'), 'Managed to retrieve the calculated permissions item.');
    $this->assertFalse($this->calculatedPermissions->getItem($scope, '404-id-not-found'), 'Requesting a non-existent identifier fails correctly.');

    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['baz']);
    $this->calculatedPermissions->addItem($item);
    $this->assertEquals(['baz'], $this->calculatedPermissions->getItem($scope, 'foo')->getPermissions(), 'Adding a calculated permissions item that was already in the list overwrites the old one.');
  }

  /**
   * Tests the retrieval of all calculated permissions items.
   *
   * @covers ::getItems
   * @depends testAddItem
   */
  public function testGetItems() {
    $scope_gt = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE;
    $scope_g = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP;

    $item_a = new CalculatedGroupPermissionsItem($scope_gt, 'foo', ['baz']);
    $item_b = new CalculatedGroupPermissionsItem($scope_g, 1, ['bob', 'charlie']);
    $this->calculatedPermissions->addItem($item_a);
    $this->calculatedPermissions->addItem($item_b);

    $this->assertEquals([$item_a, $item_b], $this->calculatedPermissions->getItems(), 'Successfully retrieved all items regardless of scope.');
  }

  /**
   * Tests the retrieval of all calculated permissions items by scope.
   *
   * @covers ::getItemsByScope
   * @depends testAddItem
   */
  public function testGetItemsByScope() {
    $scope_gt = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE;
    $scope_g = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP;

    $item_a = new CalculatedGroupPermissionsItem($scope_gt, 'foo', ['baz']);
    $item_b = new CalculatedGroupPermissionsItem($scope_g, 1, ['bob', 'charlie']);
    $this->calculatedPermissions->addItem($item_a);
    $this->calculatedPermissions->addItem($item_b);

    $this->assertEquals([$item_a], $this->calculatedPermissions->getItemsByScope($scope_gt), 'Successfully retrieved all items by scope.');
  }

  /**
   * Tests merging in another CalculatedGroupPermissions object.
   *
   * @covers ::merge
   */
  public function testMerge() {
    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $scope = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE;
    $item_a = new CalculatedGroupPermissionsItem($scope, 'foo', ['baz']);
    $item_b = new CalculatedGroupPermissionsItem($scope, 'foo', ['bob', 'charlie']);
    $item_c = new CalculatedGroupPermissionsItem($scope, 'bar', []);
    $item_d = new CalculatedGroupPermissionsItem($scope, 'baz', []);

    $this->calculatedPermissions
      ->addItem($item_a)
      ->addItem($item_c)
      ->addCacheContexts(['foo'])
      ->addCacheTags(['foo']);

    $other = new CalculatedGroupPermissions();
    $other
      ->addItem($item_b)
      ->addItem($item_d)
      ->addCacheContexts(['bar'])
      ->addCacheTags(['bar']);

    $this->calculatedPermissions->merge($other);
    $this->assertNotFalse($this->calculatedPermissions->getItem($scope, 'bar'), 'Original item that did not conflict was kept.');
    $this->assertNotFalse($this->calculatedPermissions->getItem($scope, 'baz'), 'Incoming item that did not conflict was added.');
    $this->assertSame(['baz', 'bob', 'charlie'], $this->calculatedPermissions->getItem($scope, 'foo')->getPermissions(), 'Permissions were merged properly.');
    $this->assertSame(['bar', 'foo'], $this->calculatedPermissions->getCacheContexts(), 'Cache contexts were merged properly');
    $this->assertSame(['bar', 'foo'], $this->calculatedPermissions->getCacheTags(), 'Cache tags were merged properly');
  }

}
