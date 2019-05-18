<?php

/**
 * @file
 * Contains \Drupal\ckeditor_tabber\Plugin\CKEditorPlugin\CKEditorTabber.
 */

namespace Drupal\ckeditor_tabber\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "CKEditorTabber" plugin.
 *
 * @CKEditorPlugin (
 *   id = "tabber",
 *   label = @Translation("CKEditorTabber"),
 *   module = "ckeditor_tabber"
 * )
 */
class CKEditorTabber extends CKEditorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = array();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array(
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
	return drupal_get_path('module', 'ckeditor_tabber') . '/js/plugins/tabber/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
	$path = drupal_get_path('module', 'ckeditor_tabber') . '/js/plugins/tabber/icons';
    return array(
      'Tabber' => array(
        'label' => t('Add Tab'),
		'image' => $path . '/tabber.png',
      ),
    );
  }
}