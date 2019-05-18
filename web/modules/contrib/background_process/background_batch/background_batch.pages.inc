<?php

/**
 * @file
 * Pages for background batch.
 *
 * @todo Implement proper error page instead of just 404.
 */

 use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements System settings page.
 */
function background_batch_settings_form() {
  $form = [];
  $form['background_batch_delay'] = [
    '#type' => 'textfield',
    '#default_value' => \Drupal::config('background_batch.settings')->get('background_batch_delay'),
    '#title' => 'Delay',
    '#description' => t('Time in microseconds for progress refresh'),
  ];
  $form['background_batch_process_lifespan'] = [
    '#type' => 'textfield',
    '#default_value' => \Drupal::config('background_batch.settings')->get('background_batch_process_lifespan'),
    '#title' => 'Process lifespan',
    '#description' => t('Time in milliseconds for progress lifespan'),
  ];
  $form['background_batch_show_eta'] = [
    '#type' => 'checkbox',
    '#default_value' => \Drupal::config('background_batch.settings')->get('background_batch_show_eta'),
    '#title' => 'Show ETA of batch process',
    '#description' => t('Whether ETA (estimated time of arrival) information should be shown'),
  ];
  return system_settings_form($form);
}

/**
 * Implements State-based dispatcher for the batch processing page.
 */
function background_batch_page() {
  $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : FALSE;
  if (!$id) {
    return drupal_not_found();
  }

  // Retrieve the current state of batch from db.
  $data = db_query("SELECT batch FROM {batch} WHERE bid = :bid", [
    ':bid' => $id,
  ])->fetchColumn();
  if (!$data) {
    return drupal_not_found();
  }

  $batch =& batch_get();
  $batch = unserialize($data);

  // Check if the current user owns (has access to) this batch.
  $user = \Drupal::currentUser();
  if ($batch['uid'] != $user->uid) {
    return drupal_access_denied();
  }

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  switch ($op) {
    case 'start':
      return _background_batch_page_start();

    case 'do':
      return _background_batch_page_do_js();

    case 'do_nojs':
      return _background_batch_page_do_nojs();

    case 'finished':
      progress_remove_progress('_background_batch:' . $id);
      return _batch_finished();

    default:
      return RedirectResponse::('/admin/config/system/batch/overview');
  }
}

/**
 * Implements to Start a batch job in the background.
 */
function _background_batch_initiate($process = NULL) {
  require_once 'includes/batch.inc';
  $batch =& batch_get();
  $id = $batch['id'];

  $handle = 'background_batch:' . $id;
  if (!$process) {
    $process = background_process_get_process($handle);
  }

  if ($process) {
    // If batch is already in progress,
    // Goto to the status page instead of starting it.
    if ($process->exec_status == BACKGROUND_PROCESS_STATUS_RUNNING) {
      return $process;
    }
    if (
    $process->exec_status == 1 &&
    $process->start_stamp + \Drupal::config('background_process.settings')->get('background_process_redispatch_threshold', 10) < time()
    ) {
      $process = BackgroundProcess::load($process);
      $process->dispatch();
    }
    return $process;
  }
  else {
    $process = new BackgroundProcess($handle);
    $process->service_host = 'background_batch';
    if ($process->lock()) {
      $message = $batch['sets'][0]['init_message'];
      progress_initialize_progress('_' . $handle, $message);
      if (function_exists('progress_set_progress_start')) {
        progress_set_progress_start('_' . $handle, $batch['timestamp']);
      }
      else {
        db_query("UPDATE {progress} SET start = :start WHERE name = :name", [':start' => $batch['timestamp'], ':name' => '_' . $handle]);
      }
      $process->execute('_background_batch_process', [$id]);
      return $process;
    }
  }
}

/**
 * Implements Batch Start.
 */
function _background_batch_page_start() {
  _background_batch_initiate();
  if (isset($_COOKIE['has_js']) && $_COOKIE['has_js']) {
    return _background_batch_page_progress_js();
  }
  else {
    return _background_batch_page_do_nojs();
  }
}

/**
 * Implements Batch processing page with JavaScript support.
 */
function _background_batch_page_progress_js() {
  require_once 'includes/batch.inc';

  $batch = batch_get();
  $current_set = _batch_current_set();
  $request = \Drupal::request();
  $route_match = \Drupal::routeMatch();
  $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
  $title($current_set['title'], PASS_THROUGH);

  $batch['url_options']['query']['id'] = $batch['id'];

  $js_setting['batch'] = [];
  $js_setting['batch']['errorMessage'] = $current_set['error_message'] . '<br />' . $batch['error_message'];

  if (\Drupal::config('background_batch.settings')->get('background_batch_show_eta')) {
    $js_setting['batch']['initMessage'] = 'ETA: ' . t('N/A') . '<br/>' . $current_set['init_message'];
  }
  else {
    $js_setting['batch']['initMessage'] = $current_set['init_message'];
  }
  $js_setting['batch']['uri'] = Url::fromUri($batch['url'], $batch['url_options']);
  $js_setting['batch']['delay'] = \Drupal::config('background_batch.settings')->get('background_batch_delay');

  // The Assets API has totally changed. CSS, JavaScript, and libraries are now.
  return '<div id="progress">' . $js_setting . '</div>';
}

/**
 * Implements Batch Attachments Alter.
 */
function _background_batch_page_attachments_alter(&$build) {
  $build['#attached']['library'][] = 'background_batch/background-process.batch';
}

/**
 * Implements to Do one pass of execution and inform back the browser.
 */
function _background_batch_page_do_js() {
  // HTTP POST required.
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    drupal_set_message(t('HTTP POST is required.'), 'error');
    t('Error');
    return '';
  }
  $batch = &batch_get();
  $id = $batch['id'];

  drupal_save_session(FALSE);

  $percentage = t('N/A');
  $message = '';

  if ($progress = progress_get_progress('_background_batch:' . $id)) {
    $percentage = $progress->progress * 100;
    $message = $progress->message;
    progress_estimate_completion($progress);

    if (\Drupal::config('background_batch.settings')->get('background_batch_show_eta')) {
      $message = "ETA: " . ($progress->estimate ? format_date((int) $progress->estimate, 'large') : t('N/A')) . "<br/>$message";
    }
    else {
      return $message;
    }
  }

  if ($batch['sets'][$batch['current_set']]['count'] == 0) {
    // The background process has self-destructed,
    // And the batch job is done.
    $percentage = 100;
    $message = '';
  }
  elseif ($process = background_process_get_process('background_batch:' . $id)) {
    _background_batch_initiate($process);
  }
  else {
    // Not running ... and stale.
    _background_batch_initiate();
  }
  drupal_json_output([
    'status' => TRUE,
    'percentage' => sprintf("%.02f", $percentage),
    'message' => $message,
  ]);
}

/**
 * Implements Output a batch processing page without JavaScript support.
 */
function _background_batch_page_do_nojs() {
  $batch = &batch_get();
  $id = $batch['id'];
  _background_batch_initiate();
  $current_set = _batch_current_set();
  $request = \Drupal::request();
  $route_match = \Drupal::routeMatch();
  $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
  $title($current_set['title'], PASS_THROUGH);
  $new_op = 'do_nojs';
  ob_start();
  $fallback = $current_set['error_message'] . '<br />' . $batch['error_message'];

  $fallback = [
    '#type' => 'table',
    '#header' => 'maintenance_page',
    '#content' => $fallback,
    '#attributes' => [
      'show_messages' => FALSE,
    ],
  ];
  $markup = drupal_render($fallback);

  // We strip the end of the page
  // Using a marker in the template, so any.
  list($fallback) = explode('<!--partial-->', $fallback);
  print $fallback;

  $percentage = t('N/A');
  $message = '';

  // Get progress.
  if ($progress = progress_get_progress('_background_batch:' . $id)) {
    $percentage = $progress->progress * 100;
    $message = $progress->message;
    progress_estimate_completion($progress);
    // Check wether ETA information should be shown.
    if (\Drupal::config('background_batch.settings')->get('background_batch_show_eta')) {
      $message = "ETA: " . ($progress->estimate ? format_date((int) $progress->estimate, 'large') : t('N/A')) . "<br/>$message";
    }
  }
  if ($batch['sets'][$batch['current_set']]['count'] == 0) {
    // The background process has self-destructed, and the batch job is done.
    $percentage = 100;
    $message = '';
  }
  elseif ($process = background_process_get_process('background_batch:' . $id)) {
    _background_batch_initiate($process);
  }
  else {
    // Not running ... and stale.
    _background_batch_initiate();
  }
  if ($percentage == 100) {
    $new_op = 'finished';
  }

  // PHP did not die; remove the fallback output.
  ob_end_clean();

  // Merge required query parameters for batch processing into those provided by
  // batch_set() or hook_batch_alter().
  $batch['url_options']['query']['id'] = $batch['id'];
  $batch['url_options']['query']['op'] = $new_op;
  $url = Url::fromUri($batch['url'], $batch['url_options']);
  $element = [
    '#tag' => 'meta',
    '#attributes' => [
      'http-equiv' => 'Refresh',
      'content' => '0; URL=' . $url,
    ],
  ];
  drupal_add_html_head($element, 'batch_progress_meta_refresh');
  $table = [
    '#type' => 'table',
    '#header' => 'progress_bar',
    '#percent' => sprintf("%.02f", $percentage),
    '#attributes' => [
      'message' => $message,
    ],
  ];
  $markup = drupal_render($table);
  return $markup;
}
