<?php

/**
 * @file
 * Contains \Drupal\textfield_confirm\Element\EmailConfirm.
 */

namespace Drupal\textfield_confirm\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;

/**
 * Provides a form element for double-input of email addresses.
 *
 * Formats as a pair of email fields, which do not validate unless the two
 * entered email addresses match.
 *
 * @FormElement("email_confirm")
 */
class EmailConfirm extends TextfieldConfirm {

  /**
   * Converts a textfield_confirm element into an email_confirm element.
   */
  public static function processTextfieldConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::processTextfieldConfirm($element, $form_state, $complete_form);

    $element['text1']['#type'] = 'email';
    $element['text2']['#type'] = 'email';

    // Stop the email from repeating validation errors. We'll call it ourselves.
    $element['text1']['#element_validate'] = [];
    $element['text2']['#element_validate'] = [];

    return $element;
  }

  /**
   * Validates an email_confirm element.
   */
  public static function validateTextfieldConfirm(&$element, FormStateInterface $form_state, &$complete_form) {
    // Trim the values to be helpful.
    $element['text1']['#value'] = trim($element['text1']['#value']);
    $element['text2']['#value'] = trim($element['text2']['#value']);

    parent::validateTextfieldConfirm($element, $form_state, $complete_form);

    Email::validateEmail($element, $form_state, $complete_form);

    return $element;
  }

}
