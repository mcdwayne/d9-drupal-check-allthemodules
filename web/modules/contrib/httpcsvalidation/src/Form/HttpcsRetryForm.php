<?php

namespace Drupal\httpcsvalidation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HttpcsRetryForm.
 *
 * @package Drupal\httpcsvalidation\Form
 */
class HttpcsRetryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpcs_retry_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['httpcs_retry'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#default_value' => 'Y',
    ];
    $form['event'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
    ];
    $form['secondToken'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
