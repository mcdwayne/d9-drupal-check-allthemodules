<?php

namespace Drupal\contacts_events\Entity;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;

/**
 * Implementations for Order Item hooks.
 */
class OrderItemHooks {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Construct the OrderItemHooks service.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Access checks for order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   The order item.
   * @param string $operation
   *   The operation being checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account performing the operation.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(OrderItemInterface $item, $operation, AccountInterface $account) {
    // Only change access for booking order types.
    if ($item->getOrder()->bundle() == 'contacts_booking') {
      $method = "{$operation}Access";
      if (method_exists($this, $method)) {
        return $this->{$method}($item, $account);
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Update access check for order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   The order item.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account performing the operation.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function updateAccess(OrderItemInterface $item, AccountInterface $account) {
    // Forbid if the item is confirmed. It should only be cancelled.
    $order = $item->getOrder();
    $result = AccessResult::allowedIf($account->isAuthenticated() && $order->getCustomerId() == $account->id())
      ->addCacheableDependency($item)
      ->addCacheableDependency($order)
      ->addCacheableDependency($account);
    return $result->andIf(AccessResult::allowedIf($item->get('state')->value == 'pending'));
  }

  /**
   * Delete access check for order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   The order item.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account performing the operation.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function deleteAccess(OrderItemInterface $item, AccountInterface $account) {
    // Globally forbid if the item is confirmed. It should only be cancelled.
    if ($item->get('state')->value != 'pending') {
      return AccessResult::forbidden()
        ->addCacheableDependency($item);
    }

    // Otherwise use the same rules as update access.
    return $this->updateAccess($item, $account);
  }

  /**
   * Create access check for order items.
   *
   * @param string $entity_bundle
   *   The bundle to be created.
   * @param array $context
   *   The context, if any.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account performing the operation.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function createAccess($entity_bundle, array $context, AccountInterface $account) {
    // Inline entity form doesn't give us any context, so if we are on the
    // checkout tickets page, we will assume this is a check for adding a ticket
    // and allow access if the order from the route belongs to the user we're
    // checking access for.
    // @todo: See if we can get InlineEntityForm to provide some context.
    // @todo: Expand this for non ticket line items.
    if ($entity_bundle == 'contacts_ticket' && $this->routeMatch->getRouteName() == 'booking_flow') {
      /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $this->routeMatch->getParameter('commerce_order');
      return AccessResult::allowedIf($account->isAuthenticated() && $order->getCustomerId() == $account->id())
        ->addCacheableDependency($order)
        ->addCacheableDependency($account);

    }
    return AccessResult::neutral();
  }

}
