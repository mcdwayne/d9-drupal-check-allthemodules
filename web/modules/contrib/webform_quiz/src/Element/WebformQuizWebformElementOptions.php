<?php

namespace Drupal\webform_quiz\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformElementOptions;

/**
 * Provides a form element for managing webform element options.
 *
 * This element is used by select, radios, checkboxes, likert, and
 * mapping elements.
 *
 * @FormElement("webform_quiz_webform_element_options")
 */
class WebformQuizWebformElementOptions extends WebformElementOptions {


  /**
   * Processes a webform element options element.
   */
  public static function processWebformElementOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processWebformElementOptions($element, $form_state, $complete_form);

    $element['custom']['#type'] = 'webform_quiz_webform_options';

    $element['webform_quiz_number_of_points'] = [
      '#type' => 'number',
      '#title' => t('Number of points'),
      '#description' => t('Enter the number of points for this quiz question.'),
      '#default_value' => '1',
    ];

    return $element;
  }

}
