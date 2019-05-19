<?php

/**
 * Perform ajax actions for when a user clicks a quiz response.
 *
 * @param \Drupal\Core\Ajax\AjaxResponse $ajax_response
 * @param $element
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 */
function hook_webform_quiz_correct_answer_shown(Drupal\Core\Ajax\AjaxResponse $ajax_response, $element, Drupal\Core\Form\FormStateInterface $form_state) {

}

/**
 * Alter the results display of a webform quiz submission.
 *
 * @param array $build
 * @param \Drupal\webform_quiz\QuizResults $results
 */
function hook_webform_quiz_results_display_alter(&$build, \Drupal\webform_quiz\QuizResults $results) {
  $build['share'] = [
    '#type' => 'container',
  ];
  $build['share']['contents']['#markup'] = t('Share results');
}
