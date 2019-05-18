<?php

namespace Drupal\httpcsvalidation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HttpcsForm.
 *
 * @package Drupal\httpcsvalidation\Form
 */
class HttpcsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpcs_form';
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
    $form['httpcs_crea'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#default_value' => 'Y',
    ];
    $form['url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#disabled' => TRUE,
      '#default_value' => $base_url,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => $this->t('Name and firstname')],
      '#required' => TRUE,
    ];
    $form['function'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => $this->t('Position')],
      '#required' => TRUE,
    ];
    $form['company'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => $this->t('Company')],
      '#required' => TRUE,
    ];
    $form['phone'] = [
      '#type' => 'textfield',
      '#attributes' => ['placeholder' => $this->t('Phone')],
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#attributes' => ['placeholder' => $this->t('Your email address')],
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
