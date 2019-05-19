<?php

/**
 * @file
 * Contains hooks for the webform module.
 */

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_quiz\Helper\ScoreCalculator;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Add a submit handler to store additional properties for an element
 * such as the correct answer.
 */
function webform_quiz_form_webform_ui_element_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  /** @var \Drupal\webform_ui\Form\WebformUiElementEditForm $form_object */
  $form_object = $form_state->getFormObject();

  $webform = $form_object->getWebform();
  $settings = $webform->getThirdPartySetting('webform_quiz', 'settings');

  // Stop right here if this webform is not a quiz. We don't want to affect
  // the functionality of other webforms that are not quizzes.
  if (isset($settings['is_this_a_quiz']) && !$settings['is_this_a_quiz']) {
    return;
  }

  $form['#submit'][] = 'webform_quiz_form_webform_ui_element_form_submit';
}

function webform_quiz_form_webform_ui_element_form_submit(&$form, $form_state) {
  /** @var \Drupal\webform_quiz\Plugin\WebformQuizSubmitHandlerManager $plugin_manager */
  $plugin_manager = Drupal::service('plugin.manager.webform_quiz_submit_handler');
  try {
    /** @var \Drupal\webform_quiz\Plugin\WebformQuizSubmitHandler\WebformQuizElementConfigSubmit $submit_handler */
    $submit_handler = $plugin_manager
      ->createInstance('webform_quiz_element_config_submit');

    // Save quiz data for this webform element such as the correct answer
    // todo: or the correct answer description.
    $submit_handler->handleSubmit($form, $form_state);

  } catch (PluginException $e) {
    Drupal
      ::logger('webform_quiz')
      ->error(
        "There is an issue with the webform_quiz_element_config_submit plugin: {$e->getMessage()}"
      );
  } catch (EntityStorageException $e) {
    Drupal
      ::logger('webform_quiz')
      ->error("Error saving webform: {$e->getMessage()}");
  }
}

/**
 * Implements hook_webform_submission_presave().
 */
function webform_quiz_webform_submission_presave(WebformSubmission $webform_submission) {
  $webform = $webform_submission->getWebform();
  $webform_quiz_settings = $webform->getThirdPartySetting('webform_quiz', 'settings');

  // If the webform is not a quiz, everything below the if statement is
  // irrelevant.
  if (empty($webform_quiz_settings['is_this_a_quiz']) || !$webform_quiz_settings['is_this_a_quiz']) {
    return;
  }

  $calculator = new ScoreCalculator($webform_submission);
  $quiz_results = $calculator->getResults();
  $data = $webform_submission->getData();

  // Save the quiz results.
  $data['webform_quiz_number_of_points_received'] = $quiz_results->getNumberOfPointsReceived();
  $data['webform_quiz_total_number_of_points'] = $quiz_results->getTotalNumberOfPoints();
  $data['webform_quiz_score'] = $quiz_results->getScore();

  $webform_submission->setData($data);
}

/**
 * Implements hook_preprocess_HOOK().
 *
 * Add the quiz results to to the webform submission.
 */
function webform_quiz_preprocess_webform_submission(&$variables) {
  /** @var \Drupal\webform\Entity\WebformSubmission $webform */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\Entity\Webform $webform */
  $webform = $variables['webform'];

  // Check to see if the webform submission is a quiz first.
  $settings = $webform->getThirdPartySetting('webform_quiz', 'settings');
  if (!$settings['is_this_a_quiz']) {
    return;
  }

  $variables['submission']['quiz_results'] = [
    '#type' => 'webform_quiz_quiz_result_summary',
    '#webform_submission' => $webform_submission,
  ];
}
