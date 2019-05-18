<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_counter\CounterTransactionOperation;
use Drupal\entity_counter\CounterTransactionStatus;
use Drupal\entity_counter\Entity\CounterTransaction;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Drupal\entity_counter\EntityCounterSourceValue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for an entity counter source.
 *
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 */
abstract class EntityCounterSourceBase extends PluginBase implements EntityCounterSourceInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * The entity counter.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entityCounter = NULL;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity counter source ID.
   *
   * @var string
   */
  protected $sourceId;

  /**
   * The entity counter source label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity counter source status.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The weight of the entity counter source.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * Constructs an EntityCounterSourceBase object.
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
   *
   * @see \Drupal\entity_counter\Entity\EntityCounter::getSources
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $entity_type_manager->getStorage('user')->load($account->id());
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'source_id' => $this->getSourceId(),
      'label' => $this->getLabel(),
      'status' => $this->getStatus(),
      'weight' => $this->getWeight(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'source_id' => '',
      'label' => '',
      'status' => 1,
      'weight' => '',
      'settings' => [],
    ];

    $this->sourceId = $configuration['source_id'];
    $this->label = $configuration['label'];
    $this->status = $configuration['status'];
    $this->weight = $configuration['weight'];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validate operations.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function cardinality() {
    return $this->pluginDefinition['cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounter() {
    return $this->entityCounter;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityCounter(EntityCounterInterface $entity_counter) {
    $this->entityCounter = $entity_counter;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label ?: $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function createSource() {
    // Create operations.
  }

  /**
   * {@inheritdoc}
   */
  public function updateSource(array $configuration, array $original_configuration) {
    // Update operations.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSource() {
    // Delete operations.
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceId() {
    return $this->sourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceId($source_id) {
    $this->sourceId = $source_id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = $status;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'entity_counter_source_settings_summary',
      '#settings' => $this->configuration,
      '#source' => $this,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addTransaction(float $value, EntityInterface $source_entity, string $log_message = NULL) {
    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $transaction */
    $transaction = NULL;
    $values = [
      'revision_log_message' => empty($log_message) ? sprintf('Transaction generated by %s #%d.', $source_entity->getEntityTypeId(), $source_entity->id()) : $log_message,
      'entity_counter' => $this->getEntityCounter(),
      'entity_counter_source' => $this->getSourceId(),
      'entity_type' => $source_entity->getEntityTypeId(),
      'entity_id' => $source_entity->id(),
      'transaction_value' => $value,
      'operation' => CounterTransactionOperation::ADD,
      'status' => CounterTransactionStatus::QUEUED,
    ];

    // First try to load an existing transaction if source value is of type
    // absolute.
    if ($this->valueType() == EntityCounterSourceValue::ABSOLUTE) {
      $query = $this->entityTypeManager->getStorage('entity_counter_transaction')->getQuery();

      $transactions = $query
        ->condition('entity_counter.target_id', $this->getEntityCounter()->id())
        ->condition('entity_counter_source.value', $this->getSourceId())
        ->allRevisions()
        ->sort('id', 'DESC')
        ->execute();

      if (count($transactions)) {
        reset($transactions);
        $transaction = $this->entityTypeManager->getStorage('entity_counter_transaction')->loadRevision(key($transactions));
        $transaction->setNewRevision();
        $transaction->setRevisionUser($this->currentUser);
        foreach ($values as $field => $value) {
          $transaction->set($field, $value);
        }
        $transaction->save();
      }
    }

    // If empty transaction or source value is of type incremental.
    if (empty($transaction)) {
      $transaction = CounterTransaction::create($values);
      $transaction->save();
    }

    return $transaction;
  }

  /**
   * {@inheritdoc}
   */
  public function valueType() {
    return $this->pluginDefinition['value_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Apply submitted form state to configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function applyFormStateToConfiguration(FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      if (array_key_exists($key, $this->configuration)) {
        $this->configuration[$key] = $value;
      }
    }
  }

}
