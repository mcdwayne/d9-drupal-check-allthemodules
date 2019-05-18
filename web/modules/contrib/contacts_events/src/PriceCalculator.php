<?php

namespace Drupal\contacts_events;

use Drupal\commerce_advancedqueue\CommerceOrderJob;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\contacts_events\Entity\EventClass;
use Drupal\contacts_events\Entity\EventInterface;
use Drupal\contacts_events\Entity\SingleUsePurchasableEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Utility\Error;
use Drupal\devel\DevelDumperManagerInterface;

/**
 * Class PriceCalculator.
 */
class PriceCalculator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * A devel dumper for debug output.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * Construct the price calculator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Calculate the price for an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  public function calculatePrice(OrderItemInterface $order_item) {
    // Get hold of the required data.
    if ($price_map_items = $this->findPriceMap($order_item)) {
      if (!($booking_windows = $price_map_items->getBookingWindows())) {
        $this->logger->error('Unable to find booking windows for @event::@price_map.', [
          '@event' => $price_map_items->getEntity()->label(),
          '@price_map' => $price_map_items->getFieldDefinition()->getLabel(),
        ]);
      }
      if (!($classes = $price_map_items->getClasses())) {
        $this->logger->error('Unable to find classes for @event::@price_map.', [
          '@event' => $price_map_items->getEntity()->label(),
          '@price_map' => $price_map_items->getFieldDefinition()->getLabel(),
        ]);
      }
    }

    // Stop if we don't have the required information.
    if (!$price_map_items || !$booking_windows || $booking_windows->count() == 0 || empty($classes)) {
      $this->setResult($order_item);
      return;
    }

    // If we can, get the mapped price from the purchased entity.
    if ($purchased_entity = $order_item->get('purchased_entity')->entity) {
      if ($purchased_entity instanceof SingleUsePurchasableEntityInterface) {
        $mapping = $purchased_entity->getMappedPrice();
      }
    }
    // Otherwise see if we can get it from the order item.
    if (!isset($mapping) && $order_item->hasField('mapped_price')) {
      $mapped_price_items = $order_item->get('mapped_price');
      if ($mapped_price_items->count()) {
        $mapping = $mapped_price_items->first()->getValue();
      }
    }
    // Otherwise just initialise an array.
    if (!isset($mapping)) {
      $mapping = [];
    }

    // Ensure all the required keys are set.
    $mapping += [
      'booking_window' => NULL,
      'booking_window_overridden' => FALSE,
      'class' => NULL,
      'class_overridden' => FALSE,
    ];

    // If the booking window is not overridden, calculate it.
    if (!$mapping['booking_window_overridden']) {
      $booking_window = $booking_windows->findWindow();
      if (!$booking_window) {
        $this->setResult($order_item);
        return;
      }
      $mapping['booking_window'] = $booking_window->id;
    }

    // If the class is not overridden, calculate it.
    if (!$mapping['class_overridden']) {
      $matched_classes = $this->findClasses($order_item, [], $classes);

      // If there are no matched classes, return early.
      if (empty($matched_classes)) {
        $this->setResult($order_item);
        return;
      }

      // If we have multiple, attempt to use the existing selection.
      if (count($matched_classes) > 1) {
        foreach ($matched_classes as $matched_class) {
          if ($matched_class->id() == $mapping['class']) {
            $class = $matched_class;
            break;
          }
        }
      }

      // If we don't have a class, use the first.
      if (!isset($class)) {
        $class = reset($matched_classes);
      }

      // Set the class in our map, using the selected or first matched class.
      $mapping['class'] = $class->id();
    }

    // Look up our price map to get the appropriate value.
    /* @var \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItem[][] $price_map */
    $price_map = $price_map_items->getPriceMap();
    if (!isset($price_map[$mapping['booking_window']][$mapping['class']])) {
      // We have no suitable price, so clear and return.
      $this->setResult($order_item);
      return;
    }

    // We have a result, so set our price and mapping.
    $this->setResult($order_item, $price_map[$mapping['booking_window']][$mapping['class']]->toPrice(), $mapping);
  }

  /**
   * Set the result of a price calculation.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_price\Price|null $price
   *   The price or NULL to set it 0.
   * @param array|null $mapping
   *   The mapping or NULL to leave it unchanged.
   */
  protected function setResult(OrderItemInterface $order_item, Price $price = NULL, array $mapping = NULL) {
    /* @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity*/
    $purchased_entity = $order_item->get('purchased_entity')->entity;

    // Default price to zero.
    if (!$price) {
      $stores = $purchased_entity->getStores();
      $store = reset($stores);
      $price = new Price(0, $store->getDefaultCurrencyCode());
    }

    // If the purchasable entity is a single use item, set the values back.
    if ($purchased_entity instanceof SingleUsePurchasableEntityInterface) {
      $purchased_entity->setCalculatedPrice($price);
      if ($mapping) {
        $purchased_entity->setMappedPrice($mapping);
      }

      // Check for overrides and pull that from the purchased entity.
      if ($has_override = ($purchased_entity->getPriceOverride() !== NULL)) {
        $price = $purchased_entity->getPrice();
      }
    }

    // If an override isn't set by the purchased entity but the order item is
    // overridden, we don't want to reset it.
    if (isset($has_override) || !$order_item->isUnitPriceOverridden()) {
      $order_item->setUnitPrice($price, $has_override ?? FALSE);
    }

    // Store the mapping if the order item has the right field.
    if ($mapping && $order_item->hasField('mapped_price')) {
      $order_item->set('mapped_price', $mapping);
    }
  }

  /**
   * Find the price map field for an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   *
   * @return \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItemList|null
   *   The price map or NULL if we can't find one.
   */
  public function findPriceMap(OrderItemInterface $order_item) {
    /* @var \Drupal\contacts_events\Entity\EventInterface $event */
    $event = $order_item->getOrder()->get('event')->entity;
    // Loop over definitions to find the appropriate price map.
    foreach ($event->getFieldDefinitions() as $definition) {
      if ($definition->getType() == 'price_map' && $definition->getSetting('order_item_type') == $order_item->bundle()) {
        return $event->get($definition->getName());
      }
    }

    $this->logger->error('Unable to find price map for @order_type on @event.', [
      '@order_type' => $order_item->bundle(),
      '@event' => $event->label(),
    ]);

    return NULL;
  }

  /**
   * Find the class for an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param array $context
   *   The context for finding the class.
   * @param \Drupal\contacts_events\Entity\EventClassInterface[]|null $classes
   *   The set of classes to check against. If NULL, we retrieve it.
   *
   * @return \Drupal\contacts_events\Entity\EventClassInterface[]
   *   The matched event classes.
   */
  public function findClasses(OrderItemInterface $order_item, array $context = [], array $classes = NULL) {
    // Get our classes if we weren't given them.
    if (!isset($classes)) {
      if ($price_map = $this->findPriceMap($order_item)) {
        $classes = $price_map->getClasses();
      }
      else {
        return [];
      }
    }

    // If there are no classes, there's nothing we can do.
    if (empty($classes)) {
      $this->logger->error('Unable to find classes for @event::@price_map.', [
        '@event' => $price_map->getEntity()->label(),
        '@price_map' => $price_map->getFieldDefinition()->getLabel(),
      ]);
      return [];
    }

    // Ensure our items are sorted.
    uasort($classes, [EventClass::class, 'sort']);

    // Add the order item into the context.
    $context['order_item'] = $order_item;

    // Loop over and find the suitable matches.
    $matches = [];
    $this->debug('Looking for suitable classes.');
    foreach ($classes as $class) {
      $this->debug($class->label(), $class->id());

      // If we already have a match and this is not selectable, we can skip it.
      if (!empty($matches) && !$class->get('selectable')) {
        $this->debug('Skipping non-selectable class as we already have options.');
        continue;
      }

      // Evaluate the class.
      try {
        $this->debug('Attempting to evaluate.');
        if ($class->evaluate($context)) {
          $this->debug('Class is suitable.');

          // Add this class to our matches.
          $matches[] = $class;

          // If this is not a selectable class, this is our final, so stop.
          if (!$class->get('selectable')) {
            $this->debug('Found non-selectable class, stopping.');
            break;
          }
        }
        else {
          $this->debug('Class not suitable.');
        }
      }
      catch (\Throwable $throwable) {
        $error = Error::decodeException($throwable);
        $this->debug($error, 'Evaluation failed with an error');
        $error['%event_class'] = $class->label();
        $this->logger->error('%event_class - %type: @message in %function (line %line of %file) @backtrace_string.', $error);
      }
    }

    return $matches;
  }

  /**
   * Checks for changes that affect pricing and queues recalculations as needed.
   *
   * @param \Drupal\contacts_events\Entity\EventInterface $entity
   *   The updated event entity.
   * @param \Drupal\contacts_events\Entity\EventInterface $original
   *   The original event entity.
   *
   * @return int|false
   *   FALSE if there is no calculation required. Otherwise the number of orders
   *   queued for recalculation.
   *
   * @todo: See if this can be not specific to event entitites.
   * @todo: See if we can make it target specific order items.
   */
  public function onEntityUpdate(EventInterface $entity, EventInterface $original) {
    // Check to see if any price mappings or their dependencies have changed.
    $fields_to_check = [];
    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      // Skip anything that's not a price map.
      if ($definition->getType() == 'price_map') {
        $bundle = $definition->getSetting('order_item_type');
        $fields_to_check[$field_name][$bundle] = TRUE;

        if ($field_name = $definition->getSetting('booking_window_field')) {
          $fields_to_check[$field_name][$bundle] = TRUE;
        }

        if ($field_name = $definition->getSetting('class_field')) {
          $fields_to_check[$field_name][$bundle] = TRUE;
        }
      }
    }

    // If there are any changes, recalculate all orders.
    $bundles_to_recalculate = [];
    foreach ($fields_to_check as $field_name => $bundles) {
      if ($entity->get($field_name)->getValue() != $original->get($field_name)->getValue()) {
        $bundles_to_recalculate += $bundles;
        break;
      }
    }

    // If we're not recalculating, return.
    if (empty($bundles_to_recalculate)) {
      return FALSE;
    }

    // Enqueue the jobs.
    return $this->enqueueJobs([$entity->id()], array_keys($bundles_to_recalculate));
  }

  /**
   * Enqueue recalculation jobs.
   *
   * @param int[] $event_ids
   *   An array of event IDs.
   * @param string[] $order_item_types
   *   An array of order item types to recalculate.
   *
   * @return int
   *   The number of jobs queued.
   */
  public function enqueueJobs(array $event_ids, array $order_item_types) {
    // Find all order IDs.
    $query = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->getQuery();
    $query->condition('type', 'contacts_booking');
    $query->condition('event', $event_ids, 'IN');
    $order_ids = $query->execute();
    if (empty($order_ids)) {
      return 0;
    }

    // Build our jobs.
    $jobs = [];
    foreach ($order_ids as $order_id) {
      $jobs[] = CommerceOrderJob::create('contacts_events_recalculate_order_items', ['bundles' => $order_item_types], $order_id);
    }

    /* @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->entityTypeManager
      ->getStorage('advancedqueue_queue')
      ->load('commerce_order');
    $queue->enqueueJobs($jobs);

    return count($jobs);
  }

  /**
   * Show debug output, if we have a dumper.
   *
   * @param mixed $input
   *   An arbitrary value to output.
   * @param string|null $name
   *   Optional name for identifying the output.
   */
  protected function debug($input, $name = NULL) {
    if ($this->dumper) {
      $this->dumper->message($input, $name);
    }
  }

  /**
   * Set a dumper to retrieve debugging information.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The devel dumper.
   *
   * @return $this
   */
  public function setDumper(DevelDumperManagerInterface $dumper = NULL) {
    $this->dumper = $dumper;
    return $this;
  }

}
