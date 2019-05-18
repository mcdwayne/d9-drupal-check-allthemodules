<?php

namespace Drupal\Tests\panels_everywhere\Unit\Routing;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\panels_everywhere\Plugin\DisplayVariant\PanelsEverywhereDisplayVariant;
use Drupal\panels_everywhere\Routing\PanelsEverywhereRouteSubscriber;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\panels_everywhere\Routing\PanelsEverywhereRouteSubscriber
 * @group panels_everywhere
 */
class PanelsEverywhereRouteSubscriberTest extends UnitTestCase {

  /**
   * Tests onAlterRoutes.
   *
   * Specifically that PanelsEverywhereRouteSubscriber does nothing if there are no
   * page entities.
   */
  public function testSubscriberDoesNothingForNoPageEntities() {
    // Given.
    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = new RouteCollection();

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertEmpty($routeCollection->all());
  }

  /**
   * Tests onAlterRoutes.
   *
   * Specifically that PanelsEverywhereRouteSubscriber does nothing if there are no
   * enabled page entities.
   */
  public function testSubscriberDoesNothingForNoEnabledPageEntity() {
    // Given.
    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(FALSE);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = new RouteCollection();

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertEmpty($routeCollection->all());
  }

  /**
   * Tests onAlterRoutes.
   *
   * Specifically that PanelsEverywhereRouteSubscriber does nothing if there
   * are no variants on page entity.
   */
  public function testSubscriberDoesNothingForNoVariantsOnPageEntity() {
    // Given.
    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = new RouteCollection();

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertEmpty($routeCollection->all());
  }

  /**
   * Tests onAlterRoutes.
   *
   * Specifically that PanelsEverywhereRouteSubscriber does nothing if there
   * are no variants of plugin-id 'panels_everywhere_variant' on page entity.
   */
  public function testSubscriberDoesNothingForNoPanelsEveryWhereVariantOnPageEntity() {
    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->getVariantPluginId()->willReturn('not_panels_everywhere_variant');

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn(['some_variant_id' => $pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = new RouteCollection();

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertEmpty($routeCollection->all());
  }

  /**
   * Tests onAlterRoutes.
   *
   * Specifically that PanelsEverywhereRouteSubscriber does nothing if the
   * corresponding route for the 'panels_everywhere_variant' is not in
   * the route collection.
   */
  public function testSubscriberDoesNothingForNoVariantRouteInCollection() {
    $page_id = 'some_page_id';
    $variant_id = 'some_variant_id';

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->id()->willReturn($variant_id);
    $pageVariant->getVariantPluginId()->willReturn('panels_everywhere_variant');

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->id()->willReturn($page_id);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn(['some_variant_id' => $pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeCollection = new RouteCollection();

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertEmpty($routeCollection->all());
  }

  /**
   * Test onAlterRoutes.
   *
   * Specifically that the page-manager variant route-override is and
   * the panels_everywhere_page_id is set on the original route.
   */
  public function testSubscriberRemovesVariantRouteAndSetsPanelsEverywherePageIdForOriginalRouteInCollection() {
    $page_id = 'some_page_id';
    $variant_id = 'some_variant_id';
    $route_name_variant = "page_manager.page_view_${page_id}_${variant_id}";
    $route_name_original = 'original.route_name';

    $variantPlugin = $this->prophesize(PanelsEverywhereDisplayVariant::class);
    $variantPlugin->isRouteOverrideEnabled()->willReturn(FALSE);

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->id()->willReturn($variant_id);
    $pageVariant->getVariantPluginId()->willReturn('panels_everywhere_variant');
    $pageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->id()->willReturn($page_id);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([$variant_id => $pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeOriginal = new Route('/some-path');
    $routeVariant = new Route('/some-path');
    $routeVariant->setDefault('overridden_route_name', $route_name_original);

    $routeCollection = new RouteCollection();
    $routeCollection->add($route_name_original, $routeOriginal);
    $routeCollection->add($route_name_variant, $routeVariant);

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertNull($routeCollection->get($route_name_variant));
    self::assertEquals($page_id, $routeOriginal->getDefault('page_id'));
  }

  /**
   * Test onAlterRoutes.
   *
   * Specifically that the page-manager variant route-override is not removed
   * when the removal of route-overrides is disables.
   */
  public function testSubscriberDoesNotRemoveVariantRouteAndSetsPanelsEverywherePageIdOnItForDisabledRouteOverrideRemovalBehaviour() {
    $page_id = 'some_page_id';
    $variant_id = 'some_variant_id';
    $route_name_variant = "page_manager.page_view_${page_id}_${variant_id}";
    $route_name_original = 'original.route_name';

    $variantPlugin = $this->prophesize(PanelsEverywhereDisplayVariant::class);
    $variantPlugin->isRouteOverrideEnabled()->willReturn(TRUE);

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->id()->willReturn($variant_id);
    $pageVariant->getVariantPluginId()->willReturn('panels_everywhere_variant');
    $pageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->id()->willReturn($page_id);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([$variant_id => $pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeOriginal = new Route('/some-path');
    $routeVariant = new Route('/some-path');
    $routeVariant->setDefault('overridden_route_name', $route_name_original);

    $routeCollection = new RouteCollection();
    $routeCollection->add($route_name_original, $routeOriginal);
    $routeCollection->add($route_name_variant, $routeVariant);

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertNotNull($routeCollection->get($route_name_variant));
    self::assertEquals($page_id, $routeVariant->getDefault('page_id'));
  }

  public function testSubscriberWillSetPanelsEverywherePageIdForOtherVariantsOnThePageIfOverrideDisabledAndRemoveOverriddenRoute() {
    $page_id = 'some_page_id';
    $variant_id = 'some_variant_id';
    $other_variant_id = 'some_other_variant_id';
    $route_name_variant = "page_manager.page_view_${page_id}_${variant_id}";
    $route_name_other_variant = "page_manager.page_view_${page_id}_${other_variant_id}";

    $variantPlugin = $this->prophesize(PanelsEverywhereDisplayVariant::class);
    $variantPlugin->isRouteOverrideEnabled()->willReturn(FALSE);

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->id()->willReturn($variant_id);
    $pageVariant->getVariantPluginId()->willReturn('panels_everywhere_variant');
    $pageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $otherPageVariant = $this->prophesize(PageVariantInterface::class);
    $otherPageVariant->id()->willReturn($other_variant_id);
    $otherPageVariant->getVariantPluginId()->willReturn('other_variant');

    $pageEntity = $this->prophesize(PageInterface::class);
    $pageEntity->id()->willReturn($page_id);
    $pageEntity->status()->willReturn(TRUE);
    $pageEntity->getVariants()->willReturn([
      $variant_id => $pageVariant->reveal(),
      $other_variant_id => $otherPageVariant->reveal(),
    ]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->loadMultiple()->willReturn([ $pageEntity->reveal() ]);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('page')->willReturn($pageStorage->reveal());

    $cacheTagsInvalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);

    $routeVariant = new Route('/some-path');
    $routeOtherVariant = new Route('/some-path');

    $routeCollection = new RouteCollection();
    $routeCollection->add($route_name_variant, $routeVariant);
    $routeCollection->add($route_name_other_variant, $routeOtherVariant);

    $event = new RouteBuildEvent($routeCollection);

    // When.
    $subscriber = new PanelsEverywhereRouteSubscriber($entityTypeManager->reveal(), $cacheTagsInvalidator->reveal());
    $subscriber->onAlterRoutes($event);

    // Then.
    self::assertNull($routeCollection->get($route_name_variant));
    self::assertEquals($page_id, $routeOtherVariant->getDefault('page_id'));
  }

}
