<?php

/**
 * @file
 * Documentation for Gutenberg module APIs.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * ☠️ DEPRECATED ☠️
 * You can use Drupal libraries. Check gutenberg.libraries.yml for an example.
 * Modify the list of CSS and JS files for blocks.
 *
 * @param $js_files_edit
 *   An array of all js files to be included on the editor.
 * @param $css_files_edit
 *   An array of all css files to be included on the editor.
 * @param $css_files_view
 *   An array of all css files to be included on the node view.
 */
function hook_gutenberg_blocks_alter(array &$js_files_edit, array &$css_files_edit, array &$css_files_view) {
  $js_files_edit[] = '/path/to/js/files';
  $css_files_edit[] = '/path/to/css/files';
  $css_files_view[] = '/path/to/css/files';
}