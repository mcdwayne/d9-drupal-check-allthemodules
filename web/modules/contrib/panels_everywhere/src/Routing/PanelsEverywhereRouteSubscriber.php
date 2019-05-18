<?php

/**
 * @file
 * Contains \Drupal\panels_everywhere\Routing\PanelsEverywhereRouteSubscriber.
 */

namespace Drupal\panels_everywhere\Routing;

use Drupal\Core\Display\VariantInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\page_manager\Routing\PageManagerRoutes;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\page_manager\PageInterface;

/**
 * Associates a route with a Page Manager page, if it exists
 */
class PanelsEverywhereRouteSubscriber extends PageManagerRoutes {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityStorage->loadMultiple() as $entity_id => $entity) {
      // If the page is disabled or if it's not set to disable route override, skip processing it.
      if (
        !$entity->status() ||
        !$entity->getVariants()
      ) {
        continue;
      }

      $other_variants = [];
      $panels_everywhere_variant_present = FALSE;

      foreach ($entity->getVariants() AS $variant_id => $variant) {
        if ($variant->getVariantPluginId() != 'panels_everywhere_variant') {
          $other_variants[$variant_id] = $variant;
          continue;
        }

        $panels_everywhere_variant_present = TRUE;
        $route = $this->getRouteAndCleanup($entity, $variant, $collection);

        if (is_null($route)) {
          continue;
        }

        $route->setDefault('page_id', $entity->id());
      }

      if ($panels_everywhere_variant_present) {
        foreach ($other_variants AS $variant_id => $variant) {
          $variant_route = $this->getRouteFor($entity, $variant, $collection);

          if ($variant_route) {
            $variant_route->setDefault('page_id', $entity->id());
          }
        }
      }
    }
  }

  /**
   * Retrieves relevant route and removes page-manager override if necessary.
   *
   * The page_manager-route for the given variant will be removed if
   * route-override is disabled, even if this will lead to a page not found.
   * This is intended as it allows other variants on the page to provide
   * the main page content.
   *
   * @param PageInterface $page
   *   A page-manager page entity.
   * @param PageVariantInterface $variant
   *   A variant on the page entity.
   * @param $collection
   *   A collection of known routeis.
   *
   * @return null|\Symfony\Component\Routing\Route
   *   Will return NULL if the variant route can not be found.
   *   Will return the variant route if route override is enabled.
   *   Will return the original route if route override is disabled.
   *   Will return NULL if the variant route is not overriding anything.
   *
   */
  protected function getRouteAndCleanup(PageInterface $page, PageVariantInterface $variant, RouteCollection $collection) {
    $page_id = $page->id();
    $variant_id = $variant->id();
    $route_name = "page_manager.page_view_${page_id}_${variant_id}";

    $variantRoute = $this->getRouteFor($page, $variant, $collection);
    if (is_null($variantRoute)) {
      return NULL;
    }

    if ($variant->getVariantPlugin()->isRouteOverrideEnabled()) {
      return $variantRoute;
    }

    $route_name_original = $variantRoute->getDefault('overridden_route_name');

    $collection->remove($route_name);
    return $collection->get($route_name_original);
  }

  /**
   * Retrieves the relevant route.
   *
   * @param PageInterface $page
   *   A page-manager page entity.
   * @param PageVariantInterface $variant
   *   A variant on the page entity.
   * @param $collection
   *   A collection of known routes.
   *
   * @return null|\Symfony\Component\Routing\Route
   *   The relevant route or NULL if the route could not be found.
   */
  protected function getRouteFor(PageInterface $page, PageVariantInterface $variant, RouteCollection $collection) {
    $page_id = $page->id();
    $variant_id = $variant->id();
    $route_name = "page_manager.page_view_${page_id}_${variant_id}";

    return $collection->get($route_name);
  }

}
