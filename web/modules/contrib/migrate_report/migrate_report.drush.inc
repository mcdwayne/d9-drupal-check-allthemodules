<?php

/**
 * @file
 * Command-line runner for migrate_report module.
 */

use Drupal\migrate_report\MigrateReportHelper;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function migrate_report_drush_command() {
  $items['migrate-report-generate'] = [
    'description' => 'Generates a report based on last migration. The file is saved in directory configured via UI. Alternatively --destination=/path/to/destination can be used to pass the report location.',
    'options' => [
      'destination' => 'If passed, the report will be placed in that directory. Supports also Drupal stream wrappers.',
    ],
    'examples' => [
      'migrate-report-generate' => 'Generate a .txt file in the UI configured directory',
      'mrg --destination=/path/to/reports' => 'Generate a .txt file in /path/to/reports directory',
    ],
    'drupal dependencies' => ['migrate'],
    'aliases' => ['mrg'],
    'callback' => 'drush_migrate_report_generate',
  ];
  return $items;
}

/**
 * Generates a migrate report.
 */
function drush_migrate_report_generate() {
  $path = drush_get_option('destination');

  if (($reason = MigrateReportHelper::canGenerate($path)) !== TRUE) {
    $message = dt('The report cannot be generated due to following reasons:') . "\n" . implode("\n", $reason);
    drush_log($message, LogLevel::WARNING);
  }

  if ($file = MigrateReportHelper::generate($path)) {
    drush_log(dt("Generated report: @report.", ['@report' => $file]), LogLevel::SUCCESS);
  }
  else {
    drush_log(dt('Error generating report'), LogLevel::ERROR);
  }
}
