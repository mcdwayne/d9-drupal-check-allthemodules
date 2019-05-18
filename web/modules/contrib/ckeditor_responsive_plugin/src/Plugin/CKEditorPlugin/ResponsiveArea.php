<?php

namespace Drupal\ckeditor_responsive_plugin\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "responsivearea" plugin.
 *
 * @CKEditorPlugin(
 *   id = "responsivearea",
 *   label = @Translation("Responsive area"),
 * )
 */
class ResponsiveArea extends CKEditorPluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  public function isInternal() {
    return false;
  }
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $plugin_path = drupal_get_path('module','ckeditor_responsive_plugin');
    return $plugin_path . '/js/plugins/responsivearea/plugin.js';
  }

  public function getButtons() {
    $plugin_path = drupal_get_path('module','ckeditor_responsive_plugin');
    $path = $plugin_path.'/js/plugins/responsivearea/images/';
    return [
      'AddResponsiveArea'=> [
        'label' => t('Add a responsive area'),
        'image' => $path.'responsivearea.png',
      ]
    ];
  }

  public function getCssFiles(Editor $editor) {
    $plugin_path = drupal_get_path('module','ckeditor_responsive_plugin');
    $path = $plugin_path.'/js/plugins/responsivearea/';
    return [
      $path.'responsivearea.css',
    ];
  }

}
