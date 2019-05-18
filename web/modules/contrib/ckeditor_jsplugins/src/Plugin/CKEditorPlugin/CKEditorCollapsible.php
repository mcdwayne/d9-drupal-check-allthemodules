<?php
/**
 * @file
 * Contains \Drupal\ckeditor_jsplugins\Plugin\CKEditorPlugin\CKEditorCollapsible.
 */

namespace Drupal\ckeditor_jsplugins\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "CKEditorCollapsible" plugin.
 *
 * @CKEditorPlugin (
 *   id = "collapsible",
 *   label = @Translation("CKEditorCollapsible"),
 *   module = "ckeditor_jsplugins"
 * )
 */
class CKEditorCollapsible extends CKEditorPluginBase {

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
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_jsplugins') . '/js/plugins/collapsible/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ckeditor_jsplugins') . '/js/plugins/collapsible/icons';
    return array(
      'Collapsible' => array(
        'label' => t('Add Collapsible'),
        'image' => $path . '/collapsible.png',
      ),
    );
  }

}
