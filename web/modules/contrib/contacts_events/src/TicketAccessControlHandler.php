<?php

namespace Drupal\contacts_events;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Ticket entity.
 *
 * @see \Drupal\contacts_events\Entity\Ticket.
 */
class TicketAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('current_route_match')
    );
  }

  /**
   * Constructs the ticket access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeInterface $entity_type, CurrentRouteMatch $route_match) {
    parent::__construct($entity_type);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\contacts_events\Entity\TicketInterface $entity */
    // Defer access to the ticket's order item.
    if ($item = $entity->getOrderItem()) {
      return $item->access($operation, $account, TRUE);
    }

    // Otherwise, check the manage bookings permission.
    return AccessResult::allowedIfHasPermission($account, 'can manage bookings for contacts_events');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Allow if we have the manage bookings permission.
    if ($account->hasPermission('can manage bookings for contacts_events')) {
      return AccessResult::allowed()
        ->addCacheContexts(['user.permissions']);
    }

    // Inline entity form doesn't give us any context, so if we are on the
    // checkout tickets page, we will assume this is a check for adding a ticket
    // and allow access if the order from the route belongs to the user we're
    // checking access for.
    // @todo: See if we can get InlineEntityForm to provide some context.
    if ($this->routeMatch->getRouteName() == 'booking_flow') {
      /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $this->routeMatch->getParameter('commerce_order');
      return AccessResult::allowedIf($account->isAuthenticated() && $order->getCustomerId() == $account->id())
        ->addCacheableDependency($account);
    }

    // Otherwise return neutral so other modules can have a say.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Restrict the price override to accounts that can manage bookings.
    if ($field_definition->getName() == 'price_override') {
      return AccessResult::allowedIfHasPermission($account, 'can manage bookings for contacts_events');
    }

    // Otherwise use the default.
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
