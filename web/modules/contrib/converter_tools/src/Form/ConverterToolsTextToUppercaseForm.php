<?php

namespace Drupal\converter_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for convert text to uppercase.
 */
class ConverterToolsTextToUppercaseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'converter_tools_text_uppercase';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['converter_tools_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Converter Text to Uppercase'),
      '#rows' => 20,
      '#cols' => 100,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
    ];

    $form['converter_tools_result'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Result'),
      '#attributes' => ['readonly' => 'readonly'],
      '#disabled' => TRUE,
      '#rows' => 20,
      '#cols' => 100,
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

    $text = $form_state->getValue('converter_tools_text');

    $result = strtoupper($text);

    $form_state->setValue('converter_tools_result', $result);

    $form_state->setRebuild();

  }

}
