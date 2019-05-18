<?php

namespace Drupal\entity_counter\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_counter\CounterTransactionOperation;
use Drupal\entity_counter\CounterTransactionStatus;

/**
 * Defines an entity counter transaction entity class.
 *
 * @ContentEntityType(
 *   id = "entity_counter_transaction",
 *   label = @Translation("Counter transaction"),
 *   label_singular = @Translation("Counter transaction"),
 *   label_plural = @Translation("Counter transactions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count counter transaction",
 *     plural = "@count counter transactions",
 *   ),
 *   admin_permission = "administer entity_counter_transaction",
 *   permission_granularity = "entity_type",
 *   base_table = "entity_counter_transaction",
 *   revision_table = "entity_counter_transaction_revision",
 *   show_revision_ui = TRUE,
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   handlers = {
 *     "storage" = "Drupal\entity_counter\CounterTransactionStorage",
 *     "access" = "Drupal\entity_counter\CounterTransactionAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "list_builder" = "Drupal\entity_counter\CounterTransactionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\entity_counter\Form\CounterTransactionForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   constraints = {
 *     "ValidCounterValueConstraint" = {}
 *   },
 *   entity_keys = {
 *     "id" = "transaction_id",
 *     "revision" = "revision_id",
 *     "entity_counter" = "entity_counter",
 *     "uid" = "revision_user",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/entity-counters/{entity_counter}/transactions/add",
 *     "edit-form" = "/admin/structure/entity-counters/{entity_counter}/transactions/{entity_counter_transaction}/edit",
 *     "cancel" = "/admin/structure/entity-counters/{entity_counter}/cancel",
 *     "collection" = "/admin/structure/entity-counters/{entity_counter}/transactions",
 *     "log" = "/admin/structure/entity-counters/{entity_counter}/log"
 *   }
 * )
 */
class CounterTransaction extends RevisionableContentEntityBase implements CounterTransactionInterface {

  use EntityChangedTrait;
  use RevisionLogEntityTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function cancel() {
    $current_user = $this->getCurrentUserId();

    $this->setNewRevision();
    $this
      ->setRevisionUserId(reset($current_user))
      ->setRevisionLogMessage($this->t('Cancel: @log', ['@log' => $this->getRevisionLogMessage()]))
      ->setTransactionValue($this->getTransactionValue() * -1)
      ->setQueued();

    if ($this->getOperation() == CounterTransactionOperation::ADD) {
      $this->setOperation(CounterTransactionOperation::CANCEL);
    }
    else {
      $this->setOperation(CounterTransactionOperation::ADD);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExceededLimit() {
    return $this->get('status')->value == CounterTransactionStatus::EXCEEDED_LIMIT;
  }

  /**
   * {@inheritdoc}
   */
  public function setExceededLimit() {
    $this->set('status', CounterTransactionStatus::EXCEEDED_LIMIT);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRecorded() {
    return $this->get('status')->value == CounterTransactionStatus::RECORDED;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecorded() {
    $this->set('status', CounterTransactionStatus::RECORDED);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isQueued() {
    return $this->get('status')->value == CounterTransactionStatus::QUEUED;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueued() {
    $this->set('status', CounterTransactionStatus::QUEUED);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel() {
    switch ($this->get('status')->value) {
      case CounterTransactionStatus::QUEUED:
        return $this->t('Queued');

      case CounterTransactionStatus::RECORDED:
        return $this->t('Recorded');

      case CounterTransactionStatus::EXCEEDED_LIMIT:
        return $this->t('Limit exceeded');

      default:
        return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation) {
    if ($operation == CounterTransactionOperation::CANCEL) {
      $this->set('operation', CounterTransactionOperation::CANCEL);
    }
    elseif ($operation == CounterTransactionOperation::ADD) {
      $this->set('operation', CounterTransactionOperation::ADD);
    }
    else {
      $operation = ((bool) $operation) ? CounterTransactionOperation::ADD : CounterTransactionOperation::CANCEL;
      $this->set('operation', $operation);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return $this->get('operation')->first()->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationLabel() {
    switch ($this->get('operation')->value) {
      case CounterTransactionOperation::CANCEL:
        return $this->t('Cancel');

      case CounterTransactionOperation::ADD:
        return $this->t('Add');

      default:
        return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityCounter(EntityCounterInterface $entity_counter) {
    $this->set('entity_counter', $entity_counter);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounter() {
    return $this->get('entity_counter')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounterId() {
    return $this->get('entity_counter')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounterSource() {
    return $this->getEntityCounter()->getSource($this->getEntityCounterSourceId());
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityCounterSourceId(string $source_id) {
    $this->set('entity_counter_source', $source_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCounterSourceId() {
    return $this->get('entity_counter_source')->first()->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return \Drupal::entityTypeManager()
      ->getStorage($this->getSourceEntityTypeId())
      ->load($this->getSourceEntityId());
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntityId($entity_id) {
    $this->set('entity_id', $entity_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityTypeId() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntityTypeId($entity_type) {
    $this->set('entity_type', $entity_type);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyTransactionValue() {
    return $this->getEntityCounter()->updateValue($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionValue() {
    return $this->get('transaction_value')->first()->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionValue(float $value) {
    $this->set('transaction_value', $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Update default descriptions.
    $fields['revision_created']
      ->setLabel(t('Revision create time'))
      ->setDescription(t('The time that the current counter transaction was created.'));

    $fields['revision_user']
      ->setLabel(t('User'))
      ->setDescription(t('The user for the counter transaction.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\entity_counter\Entity\CounterTransaction::getCurrentUserId');

    $fields['revision_log_message']
      ->setLabel(t('Transaction log message'))
      ->setDescription(t('Briefly describe the counter transaction details.'))
      ->setRequired(TRUE);

    $fields['entity_counter'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity counter'))
      ->setDescription(t('The entity counter.'))
      ->setSetting('target_type', 'entity_counter')
      ->setRequired(TRUE);

    $fields['entity_counter_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('The entity counter source ID.'))
      ->setSetting('is_ascii', TRUE)
      ->addConstraint('ValidCounterSourceId')
      ->setReadOnly(TRUE)
      ->setDefaultValue(NULL);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The type of the entity to which this transaction is related.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setReadOnly(TRUE)
      ->setDefaultValue(NULL);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity to which this transaction is related.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE)
      ->setDefaultValue(NULL);

    $fields['transaction_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Transaction value'))
      ->setDescription('The entity counter transaction value to add.')
      ->setSetting('default_value', 0.0)
      ->setSetting('precision', 20)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 15,
        'settings' => [
          'size' => 60,
        ],
      ]);

    $fields['operation'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Operation'))
      ->setDescription(t('A value indicating the operation type.'))
      ->setDefaultValue(CounterTransactionOperation::ADD)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recording status'))
      ->setDescription(t('A boolean indicating the recorded state.'))
      ->setDefaultValue(CounterTransactionStatus::QUEUED)
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the counter transaction was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteTransactionsBatch(array $items = []) {
    /** @var \Drupal\entity_counter\Entity\CounterTransaction[] $items */
    // Add one operation per transaction.
    $operations = [];
    foreach ($items as $item) {
      $operations[] = [
        ['\Drupal\entity_counter\Entity\CounterTransaction', 'deleteTransactionsBatchOperation'],
        [$item],
      ];
    }

    $batch = [
      'title' => t('Deleting entity counter transactions'),
      'operations' => $operations,
      'finished' => ['\Drupal\entity_counter\Entity\CounterTransaction', 'deleteTransactionsBatchFinish'],
    ];

    if (count($operations)) {
      batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteTransactionsBatchFinish($success, $results, $operations) {
    if (!$success) {
      drupal_set_message(t('Finished with an error.'), 'error');
    }

    $results += [
      'failed' => [],
      'success' => [],
    ];
    $failed = count($results['failed']);
    $total = count($results['success']) + $failed;
    drupal_set_message(t('@count transactions deleted.', ['@count' => $total]));

    if ($failed) {
      $t_args = [
        '@count' => $failed,
        '@items' => implode(', ', $results['failed']),
      ];
      drupal_set_message(t('@count transactions failed: @items.', $t_args), 'error');
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function deleteTransactionsBatchOperation($item, &$context) {
    $transaction = \Drupal::entityTypeManager()->getStorage('entity_counter_transaction')->load($item);

    try {
      $transaction->delete();
      $context['results']['success'][] = $item;
      $context['message'] = t('Deleted @item transaction.', ['@item' => $item]);
    }
    catch (\Exception $exception) {
      $context['results']['failed'][] = $item;
      $context['message'] = t('Failed @item', ['@item' => $item]);
    }
  }

}
