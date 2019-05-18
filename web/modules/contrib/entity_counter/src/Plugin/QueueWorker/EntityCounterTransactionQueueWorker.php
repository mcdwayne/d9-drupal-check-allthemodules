<?php

namespace Drupal\entity_counter\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_counter\Exception\EntityCounterException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process entity counter transaction queue tasks.
 *
 * @QueueWorker(
 *   id = "entity_counter_transaction",
 *   title = @Translation("Process entity counter transactions"),
 *   cron = {"time" = 60}
 * )
 */
class EntityCounterTransactionQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity counter transaction.
   *
   * @var \Drupal\entity_counter\CounterTransactionStorage
   */
  protected $entityStorage;

  /**
   * Constructs a new EntityCounterTransaction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityStorage = $entity_type_manager->getStorage('entity_counter_transaction');
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
    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $transaction */
    $transaction = $this->entityStorage->loadRevision($data['revision_id']);

    try {
      if (!$transaction || !$transaction->isQueued()) {
        return;
      }

      if ($transaction->applyTransactionValue() == FALSE) {
        $transaction->setExceededLimit();
      }
      else {
        $transaction->setRecorded();
      }
      $transaction->setNewRevision(FALSE);
      $transaction->save();
    }
    catch (EntityCounterException $exception) {
      watchdog_exception('entity_counter', $exception);
    }
  }

}
