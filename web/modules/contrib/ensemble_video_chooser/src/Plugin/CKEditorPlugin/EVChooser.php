<?php

namespace Drupal\ensemble_video_chooser\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "evchooser" plugin.
 *
 * @CKEditorPlugin(
 *   id = "evchooser",
 *   label = @Translation("Ensemble Video"),
 *   module = "ckeditor"
 * )
 */
class EVChooser extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ensemble_video_chooser') . '/js/plugins/evchooser/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ensemble_video_chooser') . '/js/plugins/evchooser';
    return array(
      'evchooser_button' => array(
        'label' => t('Ensemble Video'),
        'image' => $path . '/icons/evchooser.png',
      ),
    );
  }

}
