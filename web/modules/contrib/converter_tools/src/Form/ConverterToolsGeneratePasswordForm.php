<?php

namespace Drupal\converter_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for convert text to uppercase.
 */
class ConverterToolsGeneratePasswordForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'converter_tools_generate_password';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options_length_password = [];

    for ($index = 4; $index <= 99; $index = ($index + 1)) {
      $options_length_password[$index] = $index;
    }

    $form['converter_tools_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Password Length'),
      '#options' => $options_length_password,
      '#default_value' => 8,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];

    $form['converter_tools_result'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result'),
      '#attributes' => ['readonly' => 'readonly'],
      '#disabled' => TRUE,
    ];

    if ($form_state->isRebuilding() && !empty($form_state->getValue('converter_tools_result'))) {

      $result = $form_state->getValue('converter_tools_result');

      $form['converter_tools_result']['#value'] = $result;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $length = $form_state->getValue('converter_tools_length');

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $characters_length = strlen($characters);

    $result_password = '';

    for ($index = 0; $index < $length; $index = ($index + 1)) {
      $result_password .= $characters[rand(0, $characters_length - 1)];
    }

    $form_state->setValue('converter_tools_result', $result_password);

    $form_state->setRebuild();
  }

}
