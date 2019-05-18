<?php

namespace Drupal\ckeditor_balloonpanel\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "balloonpanel" plugin.
 *
 * @CKEditorPlugin(
 *   id = "balloonpanel",
 *   label = @Translation("Balloon Panel Plugin"),
 * )
 */
class BalloonPanelPlugin extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/balloonpanel/plugin.js';
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
  public function getButtons() {
    return [];
  }

}
