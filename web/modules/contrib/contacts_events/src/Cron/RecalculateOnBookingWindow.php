<?php

namespace Drupal\contacts_events\Cron;

use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Cron task for triggering price recalculations on booking window changes.
 */
class RecalculateOnBookingWindow implements CronInterface {

  use CronTrait;

  /**
   * The state key for tracking the last run of the price recalculation.
   */
  const STATE_LAST_RUN = 'contacts_events.price_recalculation.last_run';

  /**
   * Event entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eventStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The price calculator service.
   *
   * @var \Drupal\contacts_events\PriceCalculator
   */
  protected $priceCalculator;

  /**
   * Initialise additional services.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\contacts_events\PriceCalculator $price_calculator
   *   The price calculator service.
   *
   * @see \Drupal\contacts_events\Cron\CronTrait::__construct
   */
  protected function initServices(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, PriceCalculator $price_calculator) {
    $this->eventStorage = $entity_type_manager->getStorage('contacts_event');
    $this->entityFieldManager = $entity_field_manager;
    $this->priceCalculator = $price_calculator;
  }

  /**
   * {@inheritdoc}
   */
  protected function doInvoke() {
    // Get the booking window fields and the order item types they affect.
    $window_fields = [];
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('price_map');
    if (isset($field_map['contacts_event'])) {
      foreach ($field_map['contacts_event'] as $field_name => $field_info) {
        foreach ($field_info['bundles'] as $bundle) {
          $definition = $this->entityFieldManager
            ->getFieldDefinitions('contacts_event', $bundle)[$field_name];
          if ($window_field = $definition->getSetting('booking_window_field')) {
            $window_definition = $this->entityFieldManager
              ->getFieldDefinitions('contacts_event', $bundle)[$window_field];
            $order_item_type = $definition->getSetting('order_item_type');
            $window_fields[$window_field][$order_item_type] = $window_definition->getSetting('datetime_type');
          }
        }
      }
    }

    // If we have no fields, there's nothing to do.
    if (empty($window_fields)) {
      return;
    }

    // Look for any events which have passed a booking window.
    $now = $this->getCurrentTime();
    $last_run = $this->getLastRunTime();
    foreach ($window_fields as $field_name => $order_item_types) {
      foreach ($order_item_types as $order_item_type => $date_type) {
        $query = $this->eventStorage->getQuery();

        // Get the database date format.
        if ($date_type == DateTimeItem::DATETIME_TYPE_DATE) {
          $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
        }
        else {
          $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
        }

        // Check the cut off is before now.
        $query->condition("{$field_name}.cut_off", $now->format($format), '<=');

        // If we have a last run, only look more recent than that.
        if ($last_run) {
          $query->condition("{$field_name}.cut_off", $last_run->format($format), '>');
        }

        // Queue up any orders for recalculation.
        $event_ids = $query->execute();
        if (!empty($event_ids)) {
          $this->priceCalculator->enqueueJobs($event_ids, [$order_item_type]);
        }
      }
    }
  }

}
