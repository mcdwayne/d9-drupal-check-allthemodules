<?php

namespace Drupal\toolshed\Element;

use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Form\FormStateInterface;

/**
 * Create a form element for entering and validating CSS classes.
 *
 * @FormElement("css_class")
 */
class CssClassField extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    // We don't need all the extra process method for autocomplete.
    $info['#process'] = [
      static::class . '::processAjaxForm',
      static::class . '::processGroup',
    ];

    $info['#element_validate'][] = static::class . '::validateCssClasses';
    unset($info['#maxlength']);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      return is_string($input) ? preg_split('#\s+#', $input, -1, PREG_SPLIT_NO_EMPTY) : $input;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderTextfield($element) {
    if (is_array($element['#value'])) {
      $element['#attributes']['value'] = implode(' ', $element['#value']);
    }

    return parent::preRenderTextfield($element);
  }

  /**
   * Validate that the CSS classes entered here are in a valid CSS format.
   *
   * @param array $element
   *   Array definition of this css class element.
   * @param FormStateInterface $form_state
   *   The form state object, containing the build, info and values of the
   *   current form.
   */
  public static function validateCssClasses(array $element, FormStateInterface $form_state) {
    // If the class names were entered, ensure that they are valid CSS classes.
    $classStr = implode(' ', $element['#value']);
    preg_match_all('/(?<=^| )[a-z\-_][\w\-_\[\]]*(?: +|$)/i', $classStr, $matches, PREG_PATTERN_ORDER);

    // If the valid class names match the sequences seperated by spaces, then
    // all the class names were valid. Otherwise the naming violations are
    // excluded from the $matches variable.
    if (count($matches[0]) !== count($element['#value'])) {
      $cssErrors = array_diff($element['#value'], array_map('trim', $matches[0]));
      $form_state->setError($element, t(
        'All class values must be valid CSS names. This means they must start with a letter, and can only contain alphanumeric characters, dashes, underscores and brackets. These classes violate these rules: "%cssErrors"',
        ['%cssErrors' => implode(', ', $cssErrors)]
      ));
    }
  }

}
