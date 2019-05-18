<?php

namespace Drupal\httpcsvalidation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HttpcsCoForm.
 *
 * @package Drupal\httpcsvalidation\Form
 */
class HttpcsCoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpcs_co_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['httpcs_co'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#default_value' => 'Y',
    ];
    $form['url'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#default_value' => $base_url,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#attributes' => ['placeholder' => $this->t('Your email address')],
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#attributes' => ['placeholder' => $this->t('Password')],
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
