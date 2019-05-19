<?php

namespace Drupal\tckn_webform_element\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'TCKN' element.
 *
 * @WebformElement(
 *   id = "tckn_webform_element",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textfield.php/class/Textfield",
 *   label = @Translation("TCKN"),
 *   description = @Translation("Provides a form element for getting a user's TCKN (Turkish Republic Identitifation Number)"),
 *   category = @Translation("Basic elements"),
 * )
 */
class WebformTcknElement extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'multiple' => FALSE,
      'multiple__header_label' => '',
      // Form display.
      'maxlength' => 11,
      'size' => 15,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#attached']['library'][] = 'tckn_webform_element/tckn_webform_element';
    $element['#attributes']['class'][] = 'tckn';
    $element['#element_validate'][] = [get_class($this), 'validateWebformTCKNElement'];
    $element['#maxlength'] = 11;
    $element['#size'] = 12;
    $element['#type'] = 'textfield';
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * Validate TCKN element.
   */
  public static function validateWebformTcknElement(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $invalid = [
      '11111111110',
      '22222222220',
      '33333333330',
      '44444444440',
      '55555555550',
      '66666666660',
      '77777777770',
      '88888888880',
      '99999999990',
      '12345678910',
    ];
    $name = $element['#name'];
    $entered = $form_state->getValue($name);
    $chopped = substr($entered, 0, 9);
    $i = 0;
    for ($x = 0; $x < strlen($chopped); $x += 2) {
      $i += $chopped[$x];
    }
    $y = 0;
    for ($x = 1; $x < strlen($chopped); $x += 2) {
      $y += $chopped[$x];
    }
    $z = 0;
    for ($x = 0; $x < strlen($chopped); $x++) {
      $z += $chopped[$x];
    }
    $tenth = (($i * 7) - $y) % 10;
    $eleventh = ($z + $tenth) % 10;
    $final = $chopped . $tenth . $eleventh;
    if (substr($entered, 0, 1) == 0) {
      $form_state->setErrorByName($name, t("TCKN cannot start with 0."));
    }
    if (strlen($entered) < 11) {
      $form_state->setErrorByName($name, t("TCKN cannot be less than 11 characters."));
    }
    if ($entered != $final) {
      $form_state->setErrorByName($name, t("TCKN does not validate."));
    }
    foreach ($invalid as $notvalid) {
      if ($entered == $notvalid) {
        $form_state->setErrorByName($name, t('Invalid TCKN.'));
      }
    }
  }

}
