<?php
/**
 * @file
 * API documentation for XHProf Sample hooks.
 */

/**
 * Define user operations for a sample run.
 *
 * Each callback should return an array suitable to be passed
 * to theme('links'). The callback is passed an assoc array of
 * run metadata.
 */
function hook_xhprof_sample_operations() {
  return array(
    'download' => array(
      'callback' => 'mymodule_xhprof_sample_download_callback',
    ),
  );
}

/**
 * Example callback for xhprof_sample operations.
 *
 * @param array $run
 *   An array of run metadata which can be used to craft
 *     the operation definition.
 *
 * @return array
 *   Array suitable for theme('links')
 */
function mymodule_xhprof_sample_download_callback($run) {
  return array(
    'title' => t('Operation Name'),
    'href' => 'path/to/operation',
  );
}
