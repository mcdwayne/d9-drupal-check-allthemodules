<?php

namespace Drupal\cumulio\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Cumulio entity entities.
 *
 * @ingroup cumulio
 */
class CumulioEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    token_clear_cache();
    parent::submitForm($form, $form_state);
  }

}
