<?php

namespace Drupal\contacts_events\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\JobResult;
use Drupal\commerce_advancedqueue\CommerceOrderJob;
use Drupal\commerce_advancedqueue\Plugin\AdvancedQueue\JobType\CommerceOrderJobTypeBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Job type for recalculating order items.
 *
 * @AdvancedQueueJobType(
 *   id = "contacts_events_recalculate_order_items",
 *   label = @Translation("Recalculate flexible pricing order items."),
 * )
 */
class RecalculateOrderItems extends CommerceOrderJobTypeBase {

  /**
   * The price calculator service.
   *
   * @var \Drupal\contacts_events\PriceCalculator
   */
  protected $calculator;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $order_storage
   *   The order entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\contacts_events\PriceCalculator $price_calculator
   *   The price calculator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $order_storage, Connection $connection, PriceCalculator $price_calculator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $order_storage, $connection);
    $this->calculator = $price_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('commerce_order'),
      $container->get('database'),
      $container->get('contacts_events.price_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doProcess(OrderInterface $order, CommerceOrderJob $job) {
    // Check our payload.
    $payload = $job->getPayload();
    if (isset($payload['bundles'])) {
      $bundles = $payload['bundles'];
    }
    elseif (isset($payload['ids'])) {
      $ids = $payload['ids'];
    }
    else {
      return JobResult::failure('Missing payload', 0);
    }

    // Loop ove our order items, updating any as required.
    foreach ($order->getItems() as $item) {
      // Skip items not in the update list.
      if (isset($bundles) && !in_array($item->bundle(), $bundles)) {
        continue;
      }
      if (isset($ids) && !in_array($item->id(), $ids)) {
        continue;
      }

      // Update the order item price.
      $this->calculator->calculatePrice($item);
      $item->save();

      // Mark the order as needing saving.
      $job->setOrderNeedsSave();
    }

    // Return our successful result.
    return JobResult::success();
  }

}
