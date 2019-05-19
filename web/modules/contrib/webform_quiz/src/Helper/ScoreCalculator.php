<?php

namespace Drupal\webform_quiz\Helper;


use Drupal\webform_quiz\Model\WebformQuizResults;
use Drupal\webform\Entity\WebformSubmission;

class ScoreCalculator extends CalculatorBase {

  /**
   * Calculate the score based on the quiz submission.
   */
  protected function calculate() {
    $webform_submission = $this->webformSubmission;
    $webform = $webform_submission->getWebform();
    $elements = $webform->getElementsDecoded();

    $submission_data = $webform_submission->getData();

    $number_of_points_received = 0;
    $number_of_available_points = 0;

    foreach ($elements as $element_key => $element) {
      if (isset($element['#type']) && $element['#type'] !== 'webform_wizard_page') {
        $user_choice = $submission_data[$element_key];

        $number_of_available_points++;

        if (is_string($user_choice) && in_array(
            $user_choice,
            $element['#correct_answer']
          )) {
          // This indicates that the user answered the question correctly.
          $number_of_points_received++;
        }
      }
      else {
        foreach ($element as $subelement_key => $subelement) {
          if (!isset($subelement['#correct_answer'])) {
            continue;
          }
          elseif (empty($subelement['#correct_answer'])) {
            // Don't take off points for not having the correct answer
            // defined.
            if ($subelement['#type'] !== 'processed_text') {
              $number_of_points_received++;
            }
            continue;
          }

          $number_of_available_points++;

          $user_choice = @$submission_data[$subelement_key];
          if (is_scalar($user_choice) && in_array(
              $user_choice,
              $subelement['#correct_answer']
            )) {
            // This indicates that the user answered the question correctly.
            $number_of_points_received++;
          }
        }
      }
    }

    $this->results = WebformQuizResults::create([
      'webform_quiz_number_of_points_received' => $number_of_points_received,
      'webform_quiz_total_number_of_points' => $number_of_available_points,
      'webform_quiz_score' => ($number_of_points_received / $number_of_available_points) * 100
    ]);

  }

}
