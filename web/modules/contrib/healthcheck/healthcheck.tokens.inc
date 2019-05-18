<?php

/**
 * @file
 * Provides tokens support for Healthcheck.
 */

use Drupal\healthcheck\Finding\FindingStatus;

/**
 * Implements hook_token_info().
 */
function healthcheck_token_info() {
  $info = [];

  $info['types']['healthcheck_report'] = [
  'name' => t('Healthcheck Report'),
    'description' => t('Tokens for Healthcheck Reports'),
    'needs-data' => 'healthcheck_report',
  ];

  $info['tokens']['healthcheck_report']['adhoc_url'] = [
    'name' => t('Report URL'),
    'description' => t('The URL to the Healthcheck report page'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['critical_count'] = [
    'name' => t('Critical findings count'),
    'description' => t('The number of critical findings found.'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['action_requested_count'] = [
    'name' => t('Action requested count'),
    'description' => t('The number of findings where action is requested.'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['review_count'] = [
    'name' => t('Needs review count'),
    'description' => t('The number of findings needing review.'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['no_action_count'] = [
    'name' => t('No action required count'),
    'description' => t('The number of findings where no action is required.'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['not_reviewed_count'] = [
    'name' => t('Not reviewed count'),
    'description' => t('The number of findings not reviewed.'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['total_reviewed'] = [
    'name' => t('Total reviewed'),
    'description' => t('The total number of findings reviewed.'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['findings_critical'] = [
    'name' => t('Critical findings'),
    'description' => t('A list of critical findings, if any'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['findings_action_requested'] = [
    'name' => t('Action requested findings'),
    'description' => t('A list of findings where action is requested, if any'),
    'type' => 'string',
  ];

  $info['tokens']['healthcheck_report']['findings_needs_review'] = [
    'name' => t('Needs review findings'),
    'description' => t('A list of findings needing review, if any'),
    'type' => 'string',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function healthcheck_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type = 'healthcheck_report' && !empty($data['healthcheck_report'])) {
    /** @var \Drupal\healthcheck\Report\Report $report */
    $report = $data['healthcheck_report'];

    // Get the counts.
    $counts = $report->getCountsByStatus();

    // And the findings.
    $findings = $report->getFindingsByStatus();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'adhoc_url':
          $url = \Drupal\Core\Url::fromRoute('healthcheck.report_controller_runReport', [], ['absolute' => TRUE]);
          $replacements[$original] = $url->toString();
          break;

        case 'critical_count':
          $replacements[$original] = $counts[FindingStatus::CRITICAL];
          break;

        case 'action_requested_count':
          $replacements[$original] = $counts[FindingStatus::ACTION_REQUESTED];
          break;

        case 'review_count':
          $replacements[$original] = $counts[FindingStatus::NEEDS_REVIEW];
          break;

        case 'no_action_count':
          $replacements[$original] = $counts[FindingStatus::NO_ACTION_REQUIRED];
          break;

        case 'not_reviewed_count':
          $replacements[$original] = $counts[FindingStatus::NOT_PERFORMED];
          break;

        case 'total_reviewed':
          $replacements[$original] = $report->count();
          break;

        case 'findings_critical':
          if (empty($findings[FindingStatus::CRITICAL])) {
            $replacements[$original] = t('None.');
          }
          else {
            $labels = [];
            /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
            foreach ($findings[FindingStatus::CRITICAL] as $finding) {
              $labels[] = $finding->getLabel();
            }
            $replacements[$original] = implode("\r\n\r\n", $labels);
          }
          break;
//
        case 'findings_action_requested':
          if (empty($findings[FindingStatus::ACTION_REQUESTED])) {
            $replacements[$original] = t('None.');
          }
          else {
            $labels = [];
            /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
            foreach ($findings[FindingStatus::ACTION_REQUESTED] as $finding) {
              $labels[] = $finding->getLabel();
            }
            $replacements[$original] = implode("\r\n\r\n", $labels);
          }
          break;
//
        case 'findings_needs_review':
          if (empty($findings[FindingStatus::NEEDS_REVIEW])) {
            $replacements[$original] = t('None.');
          }
          else {
            $labels = [];
            /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
            foreach ($findings[FindingStatus::NEEDS_REVIEW] as $finding) {
              $labels[] = $finding->getLabel();
            }
            $replacements[$original] = implode("\r\n\r\n", $labels);
          }
          break;
      }
    }
  }

  return $replacements;
}
