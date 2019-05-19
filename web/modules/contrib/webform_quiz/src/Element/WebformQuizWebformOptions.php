<?php

namespace Drupal\webform_quiz\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformOptions;

/**
 * Provides a webform element to assist in creation of options.
 *
 * This provides a nicer interface for non-technical users to add values and
 * labels for options, possible within option groups.
 *
 * @FormElement("webform_quiz_webform_options")
 */
class WebformQuizWebformOptions extends WebformOptions {

  /**
   * {@inheritdoc}
   */
  public static function processWebformOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processWebformOptions($element, $form_state, $complete_form);

    if ($element['options']['#type'] === 'webform_multiple') {
      $element['options']['#type'] = 'webform_quiz_webform_multiple';
    }

    $element['options']['#element']['is_correct_answer'] = [
      '#type' => 'checkbox',
      '#title' => t('Is this the correct answer?'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::validateWebformOptions($element, $form_state, $complete_form);

    // Make sure there is only one correct answer.
    $values = $form_state->getValues();
    $items = $values['properties']['options']['items'];
    $correct_items = [];

    foreach ($items as $item) {
      if (isset($item['is_correct_answer']) && $item['is_correct_answer']) {
        $correct_items[] = $item;
      }
    }

    $num_correct_items = count($correct_items);
    if ($num_correct_items > 1) {
      $form_state->setError($element, t('Only one choice can be the correct answer.'));
    }
    elseif (!$num_correct_items) {
      $form_state->setError($element, t('Please select a correct answer.'));
    }
  }

}
