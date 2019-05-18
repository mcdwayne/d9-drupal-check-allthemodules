<?php

namespace Drupal\hidden_tab\Form\Base;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hidden_tab\Utility;

/**
 * To redirect based on context.
 */
class RedirectedDeleteFormBase extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if (Utility::checkRedirect()) {
      $form_state->setRedirectUrl(Utility::checkRedirect());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Utility::checkRedirect()
      ? Utility::checkRedirect()
      : parent::getCancelUrl();
  }

}
