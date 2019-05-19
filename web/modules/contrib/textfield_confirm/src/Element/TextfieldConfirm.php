<?php

/**
 * @file
 * Contains \Drupal\textfield_confirm\Element\TextfieldConfirm.
 */

namespace Drupal\textfield_confirm\Element;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for double-input of text fields.
 *
 * Formats as a pair of text fields, which do not validate unless the two
 * entered text fields match.
 *
 * @FormElement("textfield_confirm")
 */
class TextfieldConfirm extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#markup' => '',
      '#process' => [
        [$class, 'processTextfieldConfirm'],
        [$class, 'addJs'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#error_message' => t('The specified fields do not match.'),
      '#success_help' => t('Fields match: <span class="ok">yes</span>'),
      '#error_help' => t('Fields match: <span class="error">no</span>'),
      '#add_js' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $element += ['#default_value' => []];
      return $element['#default_value'] + ['text1' => '', 'text2' => ''];
    }

    $value = ['text1' => '', 'text2' => ''];
    // Throw out all invalid array keys; we only allow text1 and text2.
    foreach ($value as $allowed_key => $default) {
      // These should be strings, but allow other scalars since they might be
      // valid input in programmatic form submissions. Any nested array values
      // are ignored.
      if (isset($input[$allowed_key]) && is_scalar($input[$allowed_key])) {
        $value[$allowed_key] = (string) $input[$allowed_key];
      }
    }

    return $value;
  }

  /**
   * Adds optional javascript.
   */
  public static function addJs(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!$element['#add_js']) {
      return;
    }

    $element['#attached']['library'][] = 'textfield_confirm/textfield_confirm';

    return $element;
  }

  /**
   * Expands a textfield_confirm field into two text boxes.
   */
  public static function processTextfieldConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    $common_attributes = [
      '#size',
      '#maxlength',
      '#required',
      '#autocomplete_route_name',
      '#placeholder',
    ];

    $attributes = [
      'title',
      'description',
      'attributes',
      'autocomplete_route_name',
      'placeholder',
    ];

    $element['text1'] =  [
      '#type' => 'textfield',
      '#value' => empty($element['#value']) ? NULL : $element['#value']['text1'],
    ];

    $element['text2'] =  [
      '#type' => 'textfield',
      '#value' => empty($element['#value']) ? NULL : $element['#value']['text2'],
    ];

    // Set common properties.
    foreach ($common_attributes as $attribute) {
      if (!isset($element[$attribute])) {
        continue;
      }

      $element['text1'][$attribute] = $element[$attribute];
      $element['text2'][$attribute] = $element[$attribute];
      unset($element[$attribute]);
    }

    // Set individual properties.
    foreach ($attributes as $attribute) {
      if (isset($element['#primary_' . $attribute])) {
        $element['text1']['#' . $attribute] = $element['#primary_' . $attribute];
      }
      if (isset($element['#secondary_' . $attribute])) {
        $element['text2']['#' . $attribute] = $element['#secondary_' . $attribute];
      }
    }

    $element['text1']['#attributes']['class'][] = 'textfield-confirm-field';
    $element['text2']['#attributes']['class'][] = 'textfield-confirm-confirm';

    $element['text1']['#attributes']['data-textfield-confirm-success'] = SafeMarkup::checkPlain($element['#success_help']);
    $element['text1']['#attributes']['data-textfield-confirm-error'] = SafeMarkup::checkPlain($element['#error_help']);

    $element['#element_validate'][] = [get_called_class(), 'validateTextfieldConfirm'];
    $element['#tree'] = TRUE;
    $element['#required'] = FALSE;

    return $element;
  }

  /**
   * Validates a textfield_confirm element.
   */
  public static function validateTextfieldConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    $text1 = $element['text1'];
    $text2 = $element['text2'];

    // Use strlen() instead of empty() since '0' is a valid string value.
    // strlen() is fine to use here, since we only care if the string has some
    // length, not its exact length.
    if (strlen($text1['#value']) || strlen($text2['#value'])) {
      if ($text1['#value'] !== $text2['#value']) {
        $form_state->setError($element, $element['#error_message']);
      }
    }
    elseif ($text1['#required'] && $form_state->getUserInput()) {
      $name = !empty($element['#title']) ? $element['#title'] : FALSE;
      if (!$name) {
        $name = !empty($text1['#title']) ? $text1['#title'] : t('The');
      }
      $form_state->setError($element, t('@name field is required.', ['@name' => $name]));
    }

    // The field must be converted from a two-element array into a single string
    // regardless of validation results.
    $form_state->setValueForElement($text1, NULL);
    $form_state->setValueForElement($text2, NULL);
    $form_state->setValueForElement($element, $text1['#value']);
    $element['#value'] = $text1['#value'];

    return $element;
  }

}
