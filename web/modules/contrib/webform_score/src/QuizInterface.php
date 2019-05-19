<?php

namespace Drupal\webform_score;

use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Interface that represents a quiz question.
 */
interface QuizInterface extends WebformElementInterface {

  /**
   * Retrieve maximum possible score for this question.
   *
   * @param array $element
   *   Webform element whose maximum score to retrieve.
   *
   * @return int
   *   Maximum possible score for the provided question element.
   */
  public function getMaxScore(array $element);

  /**
   * Score a given answer.
   *
   * Calculate score for the provided webform element within the provided
   * webform submission.
   *
   * @param array $element
   *   Webform element to score.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission within which score is being calculated
   *
   * @return int
   *   Calculated score for the provided webform element within the provided
   *   webform submission.
   */
  public function score($element, WebformSubmissionInterface $webform_submission);

}
