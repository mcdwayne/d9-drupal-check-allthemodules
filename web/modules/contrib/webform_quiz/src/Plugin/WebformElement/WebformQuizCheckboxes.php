<?php

namespace Drupal\webform_quiz\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_quiz_checkboxes' element.
 *
 * @WebformElement(
 *   id = "webform_quiz_checkboxes",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Webform Quiz Checkboxes"),
 *   description = @Translation("Provides a form element for a set of checkboxes with correct answers provided."),
 *   category = @Translation("Webform Quiz"),
 * )
 */
class WebformQuizCheckboxes extends WebformQuizRadios {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // This addresses an issue where the webform_quiz_checkboxes element was not
    // appearing in the webform.
    parent::prepare($element, $webform_submission);
    $element['#type'] = 'checkboxes';
  }

}
