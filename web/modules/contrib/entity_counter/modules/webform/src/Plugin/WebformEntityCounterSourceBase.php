<?php

namespace Drupal\entity_counter_webform\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_counter\CounterTransactionOperation;
use Drupal\entity_counter\Plugin\EntityCounterSourceBaseWithEntityConditions;

/**
 * Provides a webform base class for an entity counter source.
 */
class WebformEntityCounterSourceBase extends EntityCounterSourceBaseWithEntityConditions implements WebformEntityCounterSourceBaseInterface {

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
