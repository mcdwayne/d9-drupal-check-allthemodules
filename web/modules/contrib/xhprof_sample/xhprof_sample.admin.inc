<?php
/**
 * @file
 * Admin configuration forms and functionality for XHProf Sample module.
 */

/**
 * Submit handler for cleanup of samples.
 */
function xhprof_sample_purge_samples() {
  $runclass = xhprof_sample_runclass();
  $count = $runclass::purge();

  drupal_set_message(t('Purged !count XHProf samples.', array('!count' => $count)));
}

/**
 * Page callback for outputting a list of sample files.
 */
function xhprof_sample_output_list() {
  module_load_include('inc', 'xhprof_sample');
  $runclass = xhprof_sample_runclass();
  $samples = $runclass::collectAll();

  $output = xhprof_sample_table($samples);
  $output .= theme('pager');

  return $output;
}
