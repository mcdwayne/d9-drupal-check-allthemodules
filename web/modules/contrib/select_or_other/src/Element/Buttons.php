<?php
/**
 * @file
 * Contains Drupal\select_or_other\Element\Buttons.
 */

namespace Drupal\select_or_other\Element;


use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a form element with buttons and other option.
 *
 * Properties:
 * @see ElementBase
 *
 * @FormElement("select_or_other_buttons")
 */
class Buttons extends ElementBase {

  /**
   * {@inheritdoc}
   */
  public static function processSelectOrOther(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processSelectOrOther($element, $form_state, $complete_form);

    if (!$element['#multiple']) {
      $element['select']['#type'] = 'radios';
      $element['other']['#states'] = ElementBase::prepareStates('visible', $element['#name'] . '[select]', 'value', 'select_or_other');
    }
    else {
      $element['select']['#type'] = 'checkboxes';
      $element['other']['#states'] = ElementBase::prepareStates('visible', $element['#name'] . '[select][select_or_other]', 'checked', TRUE);
    }

    return $element;
  }

}
