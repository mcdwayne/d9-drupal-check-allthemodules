<?php

namespace Drupal\wallet\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a wallet_category entity.
 *
 * @ingroup wallet_category
 */

class WalletCategoryDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?',
        array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the wallet_category list.
   */
  public function getCancelURL() {
    return new Url('entity.wallet_category.collection');
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

    \Drupal::logger('wallet')->notice('@type: deleted %title.',
        array('@type' => $this->entity->bundle(),
            '%title' => $this->entity->label(),));
    $form_state->setRedirect('view.list_category.page_1');
  }

}
