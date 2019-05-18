<?php

/**
 * @file
 * Contains custom Drush commands for competition module.
 */

/**
 * Implements hook_drush_command().
 */
function competition_drush_command() {

  $items = array();

  $items['competition-update-report-data'] = array(
    'description' => "Exports new/updated competition entries and registered users (without entries) to flat records in report data table",
    /*
     * No arguments currently
    'arguments' => array(
      'param1' => 'Some parameter',
    ),
    */
    /*
     * No options currently
    'options' => array(
      'option1' => array(
        'description' => 'Some option',
        'example-value' => 'foo',
      ),
    ),
    */
    'examples' => array(
      'drush competition-update-report-data' => 'Export all added/updated entries and users',
    ),
    'aliases' => array('compuprd'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );

  return $items;

}

/**
 * Command callback for 'competition-update-report-data'.
 */
function drush_competition_update_report_data() {

  $reporter = \Drupal::service('competition.reporter');

  $log_args = $reporter->updateAllReportData(FALSE);

  $messages = $reporter->updateAllReportDataLog($log_args, [
    'return' => [
      'html' => FALSE,
      'details' => TRUE,
      'always' => FALSE,
    ],
  ]);

  if (!empty($messages)) {
    $messages[count($messages) - 1] .= "\n";
  }
  // TODO: fold this case into log method.
  else {
    $messages = [
      "There is no new reporting data to update since the last run.",
    ];
  }

  foreach ($messages as $message) {
    drush_log(dt($message, $log_args), 'success');
  }

}
