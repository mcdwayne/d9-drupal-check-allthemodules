<?php
/**
 * @file
 * devel_php_ace.admin.inc
 */

/**
 * Page callback for admin settings.
 */
function devel_php_ace_admin_settings($form, &$form_state) {
  $form['devel_php_ace_theme'] = array(
    '#title' => t('Active theme'),
    '#type' => 'select',
    '#description' => l(t('Browse available themes'), 'http://ace.c9.io/build/kitchen-sink.html', array(
      'attributes' => array(
        'target' => '_blank',
      ),
    )),
    '#options' => devel_php_ace_get_themes(),
    '#default_value' => variable_get('devel_php_ace_theme', 'chrome'),
  );

  $form['devel_php_ace_src'] = array(
    '#title' => t('Editor version'),
    '#type' => 'select',
    '#description' => t('Which provided version of Ace should be used?'),
    '#options' => array(
      'src' => 'concatenated but not minified',
      'src-min' => 'concatenated and minified with uglify.js',
      'src-noconflict' => 'uses ace.require instead of require',
      'src-min-noconflict' => 'uses ace.require instead of require and minifed',
    ),
    '#default_value' => variable_get('devel_php_ace_src', 'src-min-noconflict'),
  );

  return system_settings_form($form);
}
