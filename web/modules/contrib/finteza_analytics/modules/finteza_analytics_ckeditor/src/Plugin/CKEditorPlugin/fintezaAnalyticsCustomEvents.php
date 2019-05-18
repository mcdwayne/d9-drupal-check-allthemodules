<?php

namespace Drupal\finteza_analytics_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Finteza Analytics" plugin.
 *
 * @CKEditorPlugin(
 *   id = "fintezaAnalyticsCustomEvents",
 *   label = @Translation("Finteza Analytics"),
 *   module = "finteza_analytics_ckeditor"
 * )
 */
class FintezaAnalyticsCustomEvents extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    if ($plugin_path = drupal_get_path('module', 'finteza_analytics_ckeditor')) {
      return $plugin_path . '/js/plugin.js';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'fintezaAnalyticsCustomEvents' => [
        'label' => t('Finteza Analytics'),
        'image' => drupal_get_path('module', 'finteza_analytics_ckeditor') . '/js/icons/fintezaAnalyticsCustomEvents.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
