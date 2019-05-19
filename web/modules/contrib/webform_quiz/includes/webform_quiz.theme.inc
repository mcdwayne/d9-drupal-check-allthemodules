<?php

/**
 * @file
 * Contains theme-related hooks for the webform_quiz module.
 */

/**
 * Implements hook_theme().
 */
function webform_quiz_theme($existing, $type, $theme, $path) {
  $info = [
    'webform_quiz_correct_answer_description' => [
      'variables' => [
        'correct_answer_description' => NULL,
        'is_user_correct' => NULL,
      ],
    ],
  ];

  return $info;
}

/**
 * Alter the default display of webform submission results.
 */
function webform_quiz_preprocess_webform_confirmation(&$variables) {
  /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $webform_submission = $variables['webform_submission'];
    $webform = $webform_submission->getWebform();
    $settings = $webform->getThirdPartySetting('webform_quiz', 'settings');

    if (
      (isset($settings['is_this_a_quiz']) && !$settings['is_this_a_quiz']) ||
      !$settings
    ) {
      // Don't alter the behavior of webforms that are not quizzes.
      return;
    }

    $build = [
      '#type' => 'webform_quiz_quiz_result_summary',
      '#webform_submission' => $webform_submission,
    ];
    $variables['message'] = $build;
}
