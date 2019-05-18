<?php

namespace Drupal\commerce_migrate\EventSubscriber;

use Drupal\commerce_order\Entity\Order;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles order and order variation references.
 *
 * @package \Drupal\commerce_migrate\EventSubscriber
 */
class MigrateOrder implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_DELETE][] = 'onPreRowDelete';
    return $events;
  }

  /**
   * Reacts to the PRE_ROW_DELETE event.
   *
   * For commerce_order rollbacks we want to preserve the order items and
   * prevent the 'usual' commerce behavior of deleting all order items
   * belonging to an order when deleting that order. That is done by
   * removing all order items from the order and saving the order.
   *
   * The extra steps to remove the order items from the order may significantly
   * increase the time to perform a rollback. To mitigate this it is best to use
   * drush and limit the number of records migrated during development. This can
   * be done with the 'limit' option or the 'idlist' option, as shown in the
   * following examples.
   * @code
   * drush migrate-import --limit=5 some_migration
   * drush migrate-import --idlist=42 some_migration
   * @endcode
   *
   * It is also possible to disable this feature and use a custom event
   * subscriber. Instructions on how to do that is in the Symfony documentation.
   *
   * @see https://symfony.com/doc/current/components/event_dispatcher.html#stopping-event-flow-propagation
   *
   * @param \Drupal\migrate\Event\MigrateRowDeleteEvent $event
   *   The event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPreRowDelete(MigrateRowDeleteEvent $event) {
    $destination_config = $event->getMigration()->getDestinationConfiguration();
    if ($destination_config['plugin'] === "entity:commerce_order") {
      $order_ids = $event->getDestinationIdValues();
      foreach ($order_ids as $order_id) {
        /** @var \Drupal\commerce_order\Entity\Order $order */
        $order = Order::load($order_id);
        if ($order && $order->hasItems()) {
          // Save the order, keeping the variations.
          /** @var \Drupal\commerce_order\Entity\OrderItem $item */
          foreach ($order->getItems() as $item) {
            $order->removeItem($item);
          }
          $order->save();
        }
      }
    }
  }

}
