<?php

namespace Drupal\converter_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for character counter.
 */
class ConverterToolsCharacterCountForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'converter_tools_character_count';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['converter_tools_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#rows' => 20,
      '#cols' => 100,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Count'),
    ];

    $form['converter_tools_result'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Total characters'),
      '#attributes' => ['readonly' => 'readonly'],
      '#disabled' => TRUE,
      '#size' => 4,
    ];

    if ($form_state->isRebuilding() && !empty($form_state->getValue('converter_tools_result'))) {

      $result = $form_state->getValue('converter_tools_result');

      $size = strlen((string) $result);

      $form['converter_tools_result']['#value'] = $result;
      $form['converter_tools_result']['#size'] = $size;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $text = $form_state->getValue('converter_tools_text');

    $result = strlen($text);

    $form_state->setValue('converter_tools_result', $result);

    $form_state->setRebuild();

  }

}
