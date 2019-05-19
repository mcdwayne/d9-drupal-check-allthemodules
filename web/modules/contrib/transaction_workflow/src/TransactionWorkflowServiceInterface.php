<?php

namespace Drupal\transaction_workflow;

/**
 * Transaction workflow service interface.
 */
interface TransactionWorkflowServiceInterface {

  /**
   * Gets the current workflow state of a given entity.
   *
   * @param string|\Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object or ID.
   * @param string|\Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The workflow transaction type object or ID.
   *
   * @return string|FALSE
   *   The current state, FALSE if no workflow transactions found for the
   *   entity.
   */
  public function getCurrentState($entity, $transaction_type);

  /**
   * Gets a list of the allowed target states from a given state.
   *
   * @param string $from_state
   *   The transition from state.
   * @param string|\Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The workflow transaction type object or ID.
   *
   * @return array
   *   The allowed target states. Empty array when the from state is final.
   *
   * @throws \InvalidArgumentException
   *   If the given from_state or transaction type ID does not exist.
   */
  public function getAllowedTransitions($from_state, $transaction_type);

}
