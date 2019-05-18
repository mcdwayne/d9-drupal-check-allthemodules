<?php

namespace Drupal\entity_counter\Controller;

use Drupal\entity_counter\Entity\CounterTransactionInterface;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for entity counter transaction entity routes.
 */
class EntityCounterTransactionController extends ControllerBase {

  /**
   * Calls a method on an entity counter and reloads the listing page.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The associated entity counter.
   * @param \Drupal\entity_counter\Entity\CounterTransactionInterface $entity_counter_transaction
   *   The entity counter transaction being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'cancel'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the collection page.
   */
  public function performOperation(EntityCounterInterface $entity_counter, CounterTransactionInterface $entity_counter_transaction, $op) {
    $entity_counter_transaction->$op()->save();
    drupal_set_message($this->t('The entity counter transaction has been updated.'));

    return $this->redirect('entity.entity_counter_transaction.collection', ['entity_counter' => $entity_counter->id()]);
  }

}
