<?php

namespace Drupal\entity_counter_commerce\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_counter\Exception\EntityCounterException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process entity counter transaction queue tasks.
 *
 * @QueueWorker(
 *   id = "commerce_entity_counter_evaluate_orders",
 *   title = @Translation("Evaluate commerce orders and create the associated entity counter transactions"),
 *   cron = {"time" = 60}
 * )
 */
class CommerceEntityCounterEvaluateOrdersQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CommerceEntityCounterEvaluateOrdersQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $entity_counter_storage = $this->entityTypeManager->getStorage('entity_counter');

    try {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $order_storage->load($data['order_id']);
      /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
      $entity_counter = $entity_counter_storage->load($data['entity_counter_id']);
      /** @var \Drupal\entity_counter\Plugin\EntityCounterSourceWithEntityConditionsInterface $source */
      $source = $entity_counter->getSource($data['entity_counter_source_id']);

      // @TODO Add a function or method for this.
      // @see entity_counter_commerce_commerce_order_presave().
      if ($entity_counter->isOpen()) {
        if ($source->isEnabled()) {
          $source->setConditionEntity($order);
          if ($source->evaluateConditions()) {
            switch ($source->getPluginId()) {
              case 'entity_counter_commerce_orders':
                $source->addTransaction(1.00, $order);
                break;

              case 'entity_counter_commerce_orders_amount':
                $transaction_value = ($order->getTotalPrice() === NULL) ? 0 : $order->getTotalPrice()->getNumber();
                // Allow other modules alter the transaction value.
                \Drupal::moduleHandler()->alter('entity_counter_commerce_orders_amount', $transaction_value, $order);
                $source->addTransaction($transaction_value, $order);
                break;
            }
          }
        }
      }
    }
    catch (EntityCounterException $exception) {
      watchdog_exception('entity_counter_commerce', $exception);
    }
  }

}
