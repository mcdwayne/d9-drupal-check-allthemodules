<?php

namespace Drupal\webform_quiz\Helper;


use Drupal\webform_quiz\Model\WebformSubmissionsHelper;

/**
 * Calculate the statistics on the percent of users with higher score.
 */
class StatsCalculator extends CalculatorBase {

  /**
   * {@inheritdoc}
   */
  protected function calculate() {
    $submissions_helper = new WebformSubmissionsHelper(
      $this->webformSubmission->getWebform()
    );

    $submissions = $submissions_helper->loadWebformSubmissions();

    // The total number of submissions for this webform. The (- 1) excludes
    // this submission from being factored in. If there is only one submission,
    // this value will stay as 1 to prevent a divide by 0 error.
    $total_submissions = count($submissions);
    $total_submissions = $total_submissions > 1 ? count($submissions) - 1 : $total_submissions;
    $number_of_submissions_with_lower_score = 0;
    /** @var \Drupal\webform_quiz\Model\WebformQuizResults $user_results */
    $user_results = (new ScoreCalculator($this->webformSubmission))->getResults();

    foreach ($submissions as $submission) {
      // Don't factor in the user's own submission.
      if ($submission->id() === $this->webformSubmission->id()) {
        continue;
      }

      /** @var \Drupal\webform_quiz\Model\WebformQuizResults $peer_results */
      $peer_results = (new ScoreCalculator($submission))->getResults();
      if ($user_results->getScore() > $peer_results->getScore()) {
        $number_of_submissions_with_lower_score++;
      }
    }

    // x = count of respondents with a score less than the current
    // score y = total respondents (including this one)p = percentage of
    // respondents who did worse than current score if y = 1 then
    // p = 0%assuming: y > 1: p = x/y-1
    $percent_scoring_better = ($number_of_submissions_with_lower_score / $total_submissions) * 100;
    $this->results = [
      'percent_that_scored_better' => round($percent_scoring_better, 2),
    ];
  }

}
