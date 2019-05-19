<?php

/*
 * @file
 * API and hook documentation for the X-Connect Translator module.
 */

/**
 * Alter file format plugins provided by other modules.
 */
function hook_tmgmt_xconnect_format_plugin_info_alter(&$file_formats) {
  // Switch the used HTML plugin controller class.
  $file_formats['html']['class'] = '\Drupal\mymodule\DifferentHtmlImplementation';
}
