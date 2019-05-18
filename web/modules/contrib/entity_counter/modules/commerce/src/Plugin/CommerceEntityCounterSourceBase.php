<?php

namespace Drupal\entity_counter_commerce\Plugin;

use Drupal\commerce\ConditionGroup;
use Drupal\commerce\ConditionManager as CommerceConditionManager;
use Drupal\Component\Uuid\Php as UuidGenerator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_counter\CounterTransactionOperation;
use Drupal\entity_counter\Plugin\EntityCounterConditionManager;
use Drupal\entity_counter\Plugin\EntityCounterSourceBaseWithEntityConditions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a commerce base class for an entity counter source.
 */
class CommerceEntityCounterSourceBase extends EntityCounterSourceBaseWithEntityConditions implements CommerceEntityCounterSourceBaseInterface {

  /**
   * Constructs a CommerceEntityCounterSourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current active user.
   * @param \Drupal\entity_counter\Plugin\EntityCounterConditionManager $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Component\Uuid\Php $uuid_generator
   *   The UUID generator.
   * @param \Drupal\commerce\ConditionManager $commerce_condition_manager
   *   The commerce condition plugin manager.
   *
   * @see \Drupal\entity_counter\Entity\EntityCounter::getSources
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account, EntityCounterConditionManager $condition_manager, UuidGenerator $uuid_generator, CommerceConditionManager $commerce_condition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $account, $condition_manager, $uuid_generator);

    $this->conditionManager = $commerce_condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.entity_counter.condition'),
      $container->get('uuid'),
      $container->get('plugin.manager.commerce_condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateConditions() {
    // Entity counter sources without conditions always apply.
    if (!$this->getConditions()->count()) {
      return TRUE;
    }

    $conditions = [];
    foreach ($this->getConditions() as $condition_id => $condition) {
      $conditions[$condition_id] = $condition;
    }
    $order_conditions = new ConditionGroup($conditions, $this->getConditionsLogic());

    return $order_conditions->evaluate($this->getConditionEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function cancelTransaction(EntityInterface $source_entity, string $log_message = NULL) {
    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $transaction */
    $transaction = NULL;

    // First try to load an exists transaction.
    $query = $this->entityTypeManager->getStorage('entity_counter_transaction')->getQuery();

    $transactions = $query
      ->condition('entity_counter.target_id', $this->getEntityCounter()->id())
      ->condition('entity_counter_source.value', $this->getSourceId())
      ->condition('entity_type.value', $source_entity->getEntityTypeId())
      ->condition('entity_id.value', $source_entity->id())
      ->condition('operation.value', CounterTransactionOperation::ADD)
      ->allRevisions()
      ->sort('revision_id', 'DESC')
      ->execute();

    if (count($transactions)) {
      reset($transactions);
      $transaction = $this->entityTypeManager->getStorage('entity_counter_transaction')->loadRevision(key($transactions));
      $transaction = $transaction->cancel();
      if (!empty($log_message)) {
        $transaction->setRevisionLogMessage($log_message);
      }
      $transaction->save();
    }

    return $transaction;
  }

}
