<?php

namespace Drupal\webform_quiz\Element;

use Drupal;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\webform_quiz\Helper\StatsCalculator;
use Drupal\webform_quiz\QuizResults;

/**
 * Provides a render element to display webform quiz results.
 *
 * @RenderElement("webform_quiz_quiz_result_summary")
 */
class WebformQuizQuizResultSummary extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#webform_submission' => NULL,
      '#pre_render' => [
        [$class, 'preRenderWebformQuizResultSummary'],
      ],
    ];
  }

  /**
   * Create webform submission information for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with webform submission information.
   */
  public static function preRenderWebformQuizResultSummary(array $element) {
    /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $webform_submission = $element['#webform_submission'];

    $results = new QuizResults($webform_submission);
    $quiz_settings = $webform_submission->getWebform()->getThirdPartySetting('webform_quiz', 'settings');
    $show_statistics = isset($quiz_settings['show_statistics']) ? $quiz_settings['show_statistics'] : 0;
    $passing_score = $quiz_settings['passing_score'];
    $percentage_correct = $results->getPercentageCorrect();

    $element['result_display'] = [
      '#type' => 'container',
    ];
    $element['result_display']['#markup'] = '<p>' . t(
        'You got @number_correct out of @total right!' . '</p>',
        [
          '@number_correct' => $results->getNumberOfPointsReceived(),
          '@total' => $results->getNumberOfPointsAvailable(),
        ]
      );

    $element['feedback_display'] = [
      '#type' => 'container',
    ];
    $markup = $percentage_correct >= $passing_score
      ? $quiz_settings['passing_score_message'] : $quiz_settings['failing_score_message'];
    $element['feedback_display']['#markup'] = $markup;

    if ($show_statistics) {
      $stats_calculator = new StatsCalculator($webform_submission);
      $stat_results = $stats_calculator->getResults();
      $better_than_percentage = $stat_results['percent_that_scored_better'];

      $element['statistics'] = [
        '#type' => 'container',
      ];
      $element['statistics']['#markup'] = t(
        'You scored better than @better_than_percentage% of respondents',
        ['@better_than_percentage' => $better_than_percentage]
      );
    }

    // Allow other modules to modify the results display.
    Drupal::moduleHandler()->alter(
      'webform_quiz_results_display',
      $element,
      $results
    );

    return $element;
  }

}
