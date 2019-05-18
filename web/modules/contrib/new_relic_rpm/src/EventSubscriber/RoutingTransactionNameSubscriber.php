<?php

namespace Drupal\new_relic_rpm\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Enhances routes with a transaction name or transaction name callback.
 *
 * The transaction name defaults to the route name, but can be overridden later
 * by further routing alterations.  A transaction name callback may be set, and
 * will be invoked by a Route Enhancer.
 *
 * @see \Drupal\new_relic_rpm\RouteEnhancer\TransactionNameEnhancer
 */
class RoutingTransactionNameSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => ['addTransactionNamesToRoutes'],
    ];
  }

  /**
   * Add transaction names/callbacks to individual routes.
   */
  public function addTransactionNamesToRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();

    // Set a transaction name for the route.
    foreach ($collection as $route_name => $route) {
      $route->setDefault('_transaction_name', $route_name);
    }

    $entity_bundle_routes = [
      'entity.node.canonical',
      'entity.node.edit_form',
      'entity.taxonomy_term.canonical',
      'entity.taxonomy_term.edit_form',
      'entity.taxonomy_term.add_form',
    ];
    foreach ($entity_bundle_routes as $entity_bundle_route) {
      if ($route = $collection->get($entity_bundle_route)) {
        $route->setDefault('_transaction_name_callback', [self::class, 'entityBundleRouteTransactionName']);
      }
    }
    if ($route = $collection->get('node.add')) {
      $route->setDefault('_transaction_name_callback', [self::class, 'nodeAddTransactionName']);
    }
  }

  /**
   * Get the transaction name for an "entity.$TYPE.$OP" route.
   */
  public static function entityBundleRouteTransactionName(Request $request) {
    $name = $request->attributes->get('_transaction_name');
    if (preg_match('/^entity\.([a-z_]+)\./', $name, $matches)) {
      $entity_type = $matches[1];
      if (($entity = $request->attributes->get($entity_type)) && $entity instanceof EntityInterface) {
        return sprintf('%s:%s', $name, $entity->bundle());
      }
    }
    return $name;
  }

  /**
   * Get the transaction name for an "node.add" route.
   */
  public static function nodeAddTransactionName(Request $request) {
    $name = $request->attributes->get('_transaction_name');
    $node_type = $request->attributes->get('node_type');
    return sprintf('%s:%s', $name, $node_type->id());
  }

}
