<?php

/**
 * Change data injected in the JS.
 *
 * @param $data
 * @param $config
 */
function hook_navigation_timing_data_alter(&$data, &$config) {
  $data['customDataVar'] = 'customValue';
  $config['debug'] = 1;
}

/**
 * Allow a piece of JS to be inserted in the loggin script.
 *
 * - Do not use comments
 * - Return minified JS
 *
 * @return {String}
 */
function hook_navigation_timing_inject_js() {
  return 'alert("Obnoxious script");';
}
