<?php

namespace Drupal\panels_everywhere\Tests\Unit\EventSubscriber;

use Drupal\Core\Display\VariantInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Routing\RouteMatch;
use Drupal\ctools\Plugin\DisplayVariant\BlockDisplayVariant;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\panels_everywhere\EventSubscriber\PanelsEverywherePageDisplayVariantSubscriber;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\panels_everywhere\EventSubscriber\PanelsEverywherePageDisplayVariantSubscriber
 * @group panels_everywhere
 */
class PanelsEverywherePageDisplayVariantSubscriberTest extends \PHPUnit_Framework_TestCase {

  public function testSubscriberDoesNotStopPropagationForAdminRoutes() {
    // Given.
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', TRUE);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertFalse($event->isPropagationStopped());
  }



  public function testSubscriberDoesNotStopPropagationForNonAdminRoutesIfNoPanelsEveryWhereVariantIsFound() {
    $page_id = 'some_page_id';

    $variantPlugin = $this->prophesize(BlockDisplayVariant::class);
    $variantPlugin->getPluginId()->willReturn('non_panels_everywhere_variant');
    $variantPlugin->getConfiguration()->willReturn([]);
    $variantPlugin->getContexts()->willReturn([]);

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->access('view')->willReturn(TRUE);
    $pageVariant->getVariantPluginId()->willReturn('');
    $pageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $page = $this->prophesize(PageInterface::class);
    $page->get('status')->willReturn(TRUE);
    $page->getVariants()->willReturn([$pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->load($page_id)->willReturn($page->reveal());
    $pageStorage->load('site_template')->willReturn(NULL);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);
    $route->setDefault('page_id', $page_id);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertFalse($event->isPropagationStopped());
  }

  public function testSubscriberStopsPropagationForNonAdminRoutesIfPanelsEverywhereVariantIsFound() {
    $page_id = 'some_page_id';

    $variantPlugin = $this->prophesize(BlockDisplayVariant::class);
    $variantPlugin->getPluginId()->willReturn('panels_everywhere_variant');
    $variantPlugin->getConfiguration()->willReturn([]);
    $variantPlugin->getContexts()->willReturn([]);

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->access('view')->willReturn(TRUE);
    $pageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $page = $this->prophesize(PageInterface::class);
    $page->get('status')->willReturn(TRUE);
    $page->getVariants()->willReturn([$pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->load($page_id)->willReturn($page->reveal());
    $pageStorage->load('site_template')->willReturn(NULL);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);
    $route->setDefault('page_id', $page_id);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertTrue($event->isPropagationStopped());
  }

  public function testSubscriberStopsPropagationForPanelsEverywhereDisplayVariantOnSiteTemplateOnly() {
    $variantPlugin = $this->prophesize(BlockDisplayVariant::class);
    $variantPlugin->getPluginId()->willReturn('panels_everywhere_variant');
    $variantPlugin->getConfiguration()->willReturn([]);
    $variantPlugin->getContexts()->willReturn([]);

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->access('view')->willReturn(TRUE);
    $pageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $page = $this->prophesize(PageInterface::class);
    $page->get('status')->willReturn(TRUE);
    $page->getVariants()->willReturn([$pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->load('site_template')->willReturn($page->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertTrue($event->isPropagationStopped());
  }

  public function testSubscriberDoesNotStopPropagationForPageDisabledPage() {
    $page = $this->prophesize(PageInterface::class);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->load('site_template')->willReturn($page->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertFalse($event->isPropagationStopped());
  }

  public function testSubscriberDoesNotStopPropagationForNotPageFound() {
    $pageStorage = $this->prophesize(EntityStorageInterface::class);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertFalse($event->isPropagationStopped());
  }

  public function testSubscriberDoesNotStopPropagationForNoAccessibleVariantFound() {
    $page_id = 'some_page_id';

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->access('view')->willReturn(FALSE);

    $page = $this->prophesize(PageInterface::class);
    $page->get('status')->willReturn(TRUE);
    $page->getVariants()->willReturn([$pageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->load($page_id)->willReturn($page->reveal());
    $pageStorage->load('site_template')->willReturn(NULL);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);
    $route->setDefault('page_id', $page_id);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertFalse($event->isPropagationStopped());
  }

  public function testSubscriberFallsBackToSiteTemplateForNoAccessiblePageVariantFound() {
    $page_id = 'some_page_idV';

    $pageVariant = $this->prophesize(PageVariantInterface::class);
    $pageVariant->access('view')->willReturn(FALSE);

    $variantPlugin = $this->prophesize(BlockDisplayVariant::class);
    $variantPlugin->getPluginId()->willReturn('panels_everywhere_variant');
    $variantPlugin->getConfiguration()->willReturn([]);
    $variantPlugin->getContexts()->willReturn([]);

    $siteTemplatePageVariant = $this->prophesize(PageVariantInterface::class);
    $siteTemplatePageVariant->access('view')->willReturn(TRUE);
    $siteTemplatePageVariant->getVariantPlugin()->willReturn($variantPlugin->reveal());

    $page = $this->prophesize(PageInterface::class);
    $page->get('status')->willReturn(TRUE);
    $page->getVariants()->willReturn([$pageVariant->reveal()]);

    $siteTemplatePage = $this->prophesize(PageInterface::class);
    $siteTemplatePage->get('status')->willReturn(TRUE);
    $siteTemplatePage->getVariants()->willReturn([$siteTemplatePageVariant->reveal()]);

    $pageStorage = $this->prophesize(EntityStorageInterface::class);
    $pageStorage->load($page_id)->willReturn($page->reveal());
    $pageStorage->load('site_template')->willReturn($siteTemplatePage->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($pageStorage->reveal());

    $subscriber = new PanelsEverywherePageDisplayVariantSubscriber($entity_type_manager->reveal());

    $route = new Route('/some-path');
    $route->setOption('_admin_route', FALSE);
    $route->setDefault('page_id', $page_id);

    $routeMatch = new RouteMatch('some.route_name', $route);

    $event = new PageDisplayVariantSelectionEvent('some_plugin_id', $routeMatch);

    // When.
    $subscriber->onSelectPageDisplayVariant($event);

    // Then.
    self::assertTrue($event->isPropagationStopped());
  }

}
