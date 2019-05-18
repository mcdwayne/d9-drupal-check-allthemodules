<?php

/**
 * @file
 * Other part of performance.
 */

/**
 * Check last cron ran.
 */
function bs_performance_other_last_cron() {
  return \Drupal::state()->get('system.cron_last') > (REQUEST_TIME - 86400);
}

/**
 * Check availability of CSS optimization.
 */
function bs_performance_other_css() {
  return \Drupal::config('system.performance')->get('css.preprocess');
}

/**
 * Check page compression.
 */
function bs_performance_other_page_compression() {
// @TODO need to find this variable
//  return variable_get('page_compression');
  return TRUE;
}

/**
 * Check availability of JavaScript optimization.
 */
function bs_performance_other_js() {
  return \Drupal::config('system.performance')->get('js.preprocess');
}

/**
 * Check availability of PHP cache.
 */
function bs_performance_other_php_apc() {
  if (version_compare(phpversion(), '5.5') >= 0) {
    if (function_exists('opcache_get_configuration')) {
      $conf = opcache_get_configuration();
      return $conf['directives']['opcache.enable'];
    }
  }
  else {
    return function_exists('apc_add');
  }

  return FALSE;
}

/**
 * Check PHP execution time.
 */
function bs_performance_other_max_execution() {
  return ini_get('max_execution_time') < 300;
}

/**
 * Check availability of Advanced CSS/JS Aggregation.
 */
function bs_performance_other_advagg() {
  return \Drupal::moduleHandler()->moduleExists('advagg')
    && \Drupal::moduleHandler()->moduleExists('advagg_css_compress')
    && \Drupal::moduleHandler()->moduleExists('advagg_js_compress');
}
