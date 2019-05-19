<?php

/**
 * @file
 * Contains \Drupal\textfield_confirm_test\Form\Text.
 */

namespace Drupal\textfield_confirm_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a textfield_confirm_test test form.
 */
class Text extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'textfield_confirm_test_text';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['testfield'] = [
      '#type' => 'textfield_confirm',
      '#title' => $this->t('The wrapper title'),
      '#description' => $this->t('The wrapper description.'),
      '#primary_title' => $this->t('Field one'),
      '#primary_description' => $this->t('This is the first field.'),
      '#secondary_title' => $this->t('Field two'),
      '#secondary_description' => $this->t('This is the second field. The value must match the first field.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The input value was: @value.', ['@value' => $form_state->getValue('testfield')]));
  }

}
