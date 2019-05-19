<?php

namespace Drupal\transaction_workflow;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\transaction\TransactionServiceInterface;

/**
 * Transaction workflow service.
 */
class TransactionWorkflowService implements TransactionWorkflowServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The transaction service.
   *
   * @var \Drupal\transaction\TransactionServiceInterface
   */
  protected $transactionService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\transaction\TransactionServiceInterface $transaction_service
   *   The transaction service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TransactionServiceInterface $transaction_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->transactionService = $transaction_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentState($entity, $transaction_type) {
    if ($last_transaction = $this->transactionService->getLastExecutedTransaction($entity, $transaction_type)) {
      $settings = $last_transaction->getType()->getPluginSettings();
      return $last_transaction->get($settings['state'])->value;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedTransitions($from_state, $transaction_type) {
    if (is_string($transaction_type)
      && !$transaction_type = $this->entityTypeManager->getStorage('transaction_type')->load($transaction_type)) {
      throw new \InvalidArgumentException('Invalid transaction type.');
    }

    $settings = $transaction_type->getPluginSettings();
    if (isset($settings["transitions_$from_state"])) {
      $target_states = empty($settings["transitions_$from_state"]) ? [] : explode(',', $settings["transitions_$from_state"]);
    }
    else {
      if (!empty($from_state)
        && !in_array($from_state, array_keys($transaction_type->getThirdPartySetting('transaction_workflow', 'states')))) {
        throw new \InvalidArgumentException(sprintf('Invalid workflow state %s.', $from_state));
      }
      else {
        $target_states = [];
      }
    }

    return $target_states;
  }

}
