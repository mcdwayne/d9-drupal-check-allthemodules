<?php

use Drupal\healthcheck\Finding\FindingStatus;

function template_preprocess_healthcheck_report(&$variables) {
  /** @var \Drupal\healthcheck\Report\Report $report */
  $report = $variables['report'];

  $variables['checks'] = [
    '#type'       => 'container',
    '#attributes' => ['class' => ['update']],
    '#attached'   => [
      'library' => [
        'healthcheck/healthcheck',
      ],
    ],
  ];

  $counts = $report->getCountsByStatus();

  // Collate them into our variables.
  $variables['critical_count'] = $counts[FindingStatus::CRITICAL];
  $variables['action_requested_count'] = $counts[FindingStatus::ACTION_REQUESTED];
  $variables['review_count'] = $counts[FindingStatus::NEEDS_REVIEW];
  $variables['no_action_count'] = $counts[FindingStatus::NO_ACTION_REQUIRED];
  $variables['not_reviewed_count'] = $counts[FindingStatus::NOT_PERFORMED];
  $variables['total_reviewed'] = $report->count();

  /** @var \Drupal\healthcheck\Plugin\HealthcheckPluginManager $plugin_mgr */
  $plugin_mgr = \Drupal::service('plugin.manager.healthcheck_plugin');
  $filters = $plugin_mgr->getTags();

  $variables['filters'] = [
    '#theme' => 'healthcheck_filter_list',
    '#filters' => $filters,
  ];

  $labels = FindingStatus::getLabels();
  $constants = FindingStatus::getTextConstants();
  $findings_by_status = $report->getFindingsByStatus();
  foreach ($findings_by_status as $status => $findings) {
    $variables['checks'][] = [
      '#theme' => 'healthcheck_status',
      '#status' => $constants[$status],
      '#label' => $labels[$status],
      '#count' => count($findings),
      '#findings' => $findings,
    ];
  }

  /**
   * Prepare variables for the report summary circle graph
   *
   * The length of the circle part for a category is a percent represented as a whole number integer, eg. 25 for 25%, NOT a decimal, eg. 0.25
   * The offset determines where on the circle to start each category of the circle by degrees, so 25% offset is (360 * 0.25) for 90 degrees
   *
   * We prepare these calculations in order starting with critical category
   */
  $total = $variables['total_reviewed'];
  $degrees_counter = 0; // track degrees to offset each new part of circle

  // Critical
  if ($variables['critical_count'] > 0) {
    $critical_percent = $variables['critical_count'] / $total;
    $variables['critical_length'] =  $critical_percent * 100;
    $variables['critical_offset'] = 360 / ($variables['critical_length'] / $total);
    $degrees_counter = $variables['critical_offset'];
  }

  // Action_requested
  if ($variables['action_requested_count'] > 0) {
    $action_requested_percent = $variables['action_requested_count'] / $total;
    $variables['action_requested_length'] = $action_requested_percent * 100;
    $variables['action_requested_offset'] = $degrees_counter;
    $degrees_counter = (360 * $action_requested_percent) + $degrees_counter;
  }

  // Needs review
  if ( $variables['review_count'] > 0) {
    $review_percent = ($variables['review_count'] / $total);
    $variables['review_length'] =  $review_percent * 100;
    $variables['review_offset'] = $degrees_counter;
    $degrees_counter = (360 * $review_percent) + $degrees_counter;
  }

  // No Action
  if ($variables['no_action_count'] > 0) {
    $no_action_percent = $variables['no_action_count'] / $total;
    $variables['no_action_length'] = $no_action_percent * 100;
    $variables['no_action_offset'] = $degrees_counter;
    $degrees_counter = (360 * $no_action_percent) + $degrees_counter;
  }

  // Not Reviewed
  if ($variables['not_reviewed_count'] > 0) {
    $not_reviewed_percent = $variables['not_reviewed_count'] / $total;
    $variables['not_reviewed_length'] = $not_reviewed_percent * 100;
    $variables['not_reviewed_offset'] = $degrees_counter;
  }
}

function template_preprocess_healthcheck_filter_list(&$variables) {
  $filters = $variables['filters'];

  foreach ($filters as $filter_id => $filter) {
    $variables['list_items'][] = [
      '#theme' => 'healthcheck_filter',
      '#filter' => $filter,
      '#filter_id' => $filter_id,
      '#label' => $filter['label'],
      '#description' => empty($filter['description']) ? '' : $filter['description'],
    ];
  }
}

function template_preprocess_healthcheck_status(&$variables) {
  $findings = $variables['findings'];

  /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
  foreach ($findings as $finding) {
    /** @var \Drupal\healthcheck\Plugin\HealthcheckPluginInterface $check */
    $check = $finding->getCheck();

    $variables['action_items'][] = [
      '#theme' => 'healthcheck_finding',
      '#finding' => $finding,
    ];
  }
}

function template_preprocess_healthcheck_finding(&$variables) {
  /** @var \Drupal\healthcheck\Finding\FindingInterface $finding */
  $finding = $variables['finding'];

  /** @var \Drupal\healthcheck\Plugin\HealthcheckPluginInterface $check */
  $check = $finding->getCheck();

  $variables['status'] = $finding->getStatus();
  $variables['key'] = $finding->getKey();
  $variables['label'] = $finding->getLabel();
  $variables['message'] = $finding->getMessage();
  $variables['check'] = $check;
  $variables['check_id'] = $check->getPluginId();
  $variables['check_label'] = $check->label();
  $variables['check_description'] = $check->getDescription();
  $variables['tags'] = $check->getTags();

  // Render data as an HTML details field.
  $data = $finding->getAllData();
  if (!empty($data)) {
    // JSON is prettier than print_r
    $description = '<pre>' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';

    $variables['data'] = [
      '#type' => 'details',
      '#title' => t('Raw details'),
      '#description' => $description,
      '#open' => FALSE,
    ];
  }
}
