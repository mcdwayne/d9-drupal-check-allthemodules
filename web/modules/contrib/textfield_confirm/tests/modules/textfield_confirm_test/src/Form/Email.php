<?php

/**
 * @file
 * Contains \Drupal\textfield_confirm_test\Form\Email.
 */

namespace Drupal\textfield_confirm_test\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a textfield_confirm_test test form.
 */
class Email extends Text {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['testfield']['#type'] = 'email_confirm';
    return $form;
  }

}
