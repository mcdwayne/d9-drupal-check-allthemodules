<?php

namespace Drupal\commerce_migrate\EventSubscriber;

use Drupal\commerce_product\Entity\Product;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles product and product variation references.
 *
 * @package \Drupal\commerce_migrate\EventSubscriber
 */
class MigrateProduct implements EventSubscriberInterface {

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
   * For commerce_product rollbacks we want to preserve the product variations
   * and prevent the 'usual' commerce behavior of deleting all variations
   * belonging to a product when deleting that product. That is done by
   * unsetting the variations field on the product and saving the product
   * before it is deleted.
   *
   * @param \Drupal\migrate\Event\MigrateRowDeleteEvent $event
   *   The event.
   */
  public function onPreRowDelete(MigrateRowDeleteEvent $event) {
    $destination_config = $event->getMigration()->getDestinationConfiguration();
    if ($destination_config['plugin'] === "entity:commerce_product") {
      $product_ids = $event->getDestinationIdValues();
      foreach ($product_ids as $product_id) {
        /** @var \Drupal\commerce_product\Entity\product $product */
        $product = Product::load($product_id);
        if (($product) && ($product->hasVariations())) {
          // Save the product, keeping the variations.
          $product->setVariations([]);
          $product->save();
        }
      }
    }
  }

}
