<?php

/**
 * @file
 * Drush interface to linkchecker functionalities.
 */

/**
 * Implements hook_drush_command().
 */
function linkchecker_drush_command() {
  $commands = [];

  $commands['linkchecker-analyze'] = [
    'description' => 'Reanalyzes content for links. Recommended after module has been upgraded.',
  ];
  $commands['linkchecker-clear'] = [
    'description' => 'Clears all link data and analyze content for links. WARNING: Custom link check settings are deleted.',
  ];
  $commands['linkchecker-check'] = [
    'description' => 'Check link status.',
    // 'options' => [
    //   'links' => 'Number of links to check in one round. Default: 1000',
    // ],
  ];

  return $commands;
}

/**
 * Callback for command linkchecker-analyze.
 */
function drush_linkchecker_analyze() {
  // @fixme
  global $base_url;
  if ($base_url == 'http://default') {
    drush_die('You MUST configure the site $base_url or provide --uri parameter.');
  }

  // @fixme
  module_load_include('admin.inc', 'linkchecker');

  // Fake $form_state to leverage _submit function.
  $form_state = [
    'values' => ['op' => t('Analyze content for links')],
    'buttons' => [],
  ];

  $node_types = linkchecker_scan_node_types();
  if (!empty($node_types) || \Drupal::config('linkchecker.settings')->get('scan_blocks')) {
    linkchecker_analyze_links_submit(NULL, $form_state);
    drush_backend_batch_process();
  }
  else {
    drush_log('No content configured for link analysis.', 'status');
  }
}

/**
 * Callback for command linkchecker-analyze.
 */
function drush_linkchecker_clear() {
  // @fixme
  global $base_url;
  if ($base_url == 'http://default') {
    drush_die('You MUST configure the site $base_url or provide --uri parameter.');
  }

  // @fixme
  module_load_include('admin.inc', 'linkchecker');

  // Fake $form_state to leverage _submit function.
  $form_state = [
    'values' => ['op' => t('Clear link data and analyze content for links')],
    'buttons' => [],
  ];

  $node_types = linkchecker_scan_node_types();
  if (!empty($node_types) || \Drupal::config('linkchecker.settings')->get('scan_blocks')) {
    linkchecker_clear_analyze_links_submit(NULL, $form_state);
    drush_backend_batch_process();
  }
  else {
    drush_log('No content configured for link analysis.', 'status');
  }
}

/**
 * Callback for command linkchecker-check.
 */
function drush_linkchecker_check() {
  drush_log('Starting link checking...', 'info');
  $run = _linkchecker_check_links();
  if (!$run) {
    drush_log('Attempted to re-run link checks while they are already running.', 'warning');
  }
  else {
    drush_log('Finished checking links.', 'completed');
  }
}
