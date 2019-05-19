<?php

namespace Drupal\wallet\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a wallet_transaction entity.
 *
 * @ingroup wallet_transaction
 */
class WalletTransactionDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */

  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */

  public function getCancelURL() {
    return new Url('entity.wallet_currency.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();
    \Drupal::logger('wallet')->notice('@type: deleted %title.', array('@type' => $this->entity->bundle(), '%title' => $this->entity->label(),));
    $form_state->setRedirect('view.list_transactions.page_1');
  }

}
