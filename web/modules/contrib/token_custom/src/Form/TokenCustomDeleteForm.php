<?php

namespace Drupal\token_custom\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for deleting a custom token entity.
 */
class TokenCustomDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#markup' => $this->t('<p>Check that this token is not in use.</p>'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
