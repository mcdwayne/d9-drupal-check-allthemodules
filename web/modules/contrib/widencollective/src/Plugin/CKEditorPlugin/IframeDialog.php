<?php

namespace Drupal\widencollective\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginContextualInterface;

/**
 * Defines the "templates" plugin.
 *
 * @CKEditorPlugin(
 *   id = "iframedialog",
 *   label = @Translation("Iframe Diaglog - Plugin for making iframe based dialogs"),
 *   module = "iframedialog"
 * )
 */
class IframeDialog extends CKEditorPluginBase implements CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return '/libraries/iframedialog/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
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
  public function isEnabled(Editor $editor) {
    // Enable this plugin once Widencollective is in use.
    $enabled = FALSE;
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        foreach ($group['items'] as $button) {
          if ($button === 'Widencollective') {
            $enabled = TRUE;
          }
        }
      }
    }

    return $enabled;
  }

}
