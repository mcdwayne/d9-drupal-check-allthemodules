<?php

namespace Drupal\converter_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Replace words in text.
 */
class ConverterToolsReplaceWordsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'converter_tools_replace_words_text';
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

    $form['converter_tools_search_by_word'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Find text'),
    ];

    $form['converter_tools_replace_for_word'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace with'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Replace'),
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

    $search_by_word = $form_state->getValue('converter_tools_search_by_word');

    $replace_for_word = $form_state->getValue('converter_tools_replace_for_word');

    $result = str_replace($search_by_word, $replace_for_word, $text);

    $form_state->setValue('converter_tools_result', $result);

    $form_state->setRebuild();

  }

}
