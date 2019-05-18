<?php

/**
 * @file
 * Define a plugin for wysiwyg API
 */

/**
 * Implements hook_wysiwyg_plugin().
 */
function inline_node_plugin() {
  $plugins['node'] = array(
    'title' => t('Inline node'),
    'vendor url' => 'http://drupal.org/project/inline',
    'icon file' => 'node.png',
    'icon title' => t('Insert or update an embedded content'),
    'settings' => array(
      'dialog' => array(
        'url' => base_path() . 'index.php?q=wysiwyg/node',
        'width' => 700,
        'height' => 500,
      ),
    ),
    // @todo Use a more granular way to validate contents for input formats.
    #'extended_valid_elements' => array('img[class|src|border=0|alt|title|width|height|align|name|style]'),
  );
  return $plugins;
}

/**
 * Implementats hook_wysiwyg_dialog().
 *
 * @see inline_node_plugin()
 */
function node_wysiwyg_dialog($instance) {
  drupal_set_title(t('Add or edit external content'));

  return drupal_get_form('inline_node_wysiwyg_dialog_form', $instance);
}

/**
 * Form builder function for node inline wysiwyg dialog form.
 *
 * @see node_wysiwyg_dialog()
 */
function inline_node_wysiwyg_dialog_form($form, &$form_state, $instance) {
  $form['#attached']['js'][] = drupal_get_path('module', 'inline') . '/plugins/dialog.js';

  $form['nid'] = array(
    '#type' => 'select',
    '#title' => t('Content to embed'),
    '#options' => array(
      1 => 'foo bar',
    ),
    '#description' => t('The selected content will be inserted and embedded into your content.'),
  );

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Insert'));
  $form['actions']['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'external',
    '#attributes' => array(
      'class' => array('form-cancel'),
    ),
  );
  return $form;
}

