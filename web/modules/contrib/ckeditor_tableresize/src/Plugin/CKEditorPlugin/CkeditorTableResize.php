<?php

namespace Drupal\ckeditor_tableresize\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;

/**
 * Defines the "Table Resize" plugin.
 *
 * @CKEditorPlugin(
 *   id = "tableresize",
 *   label = @Translation("Table Resize")
 * )
 */
class CkeditorTableResize extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Returns tableresize plugin path relative to drupal root.
   *
   * @return string
   *   Relative path to the ckeditor plugin folder
   */
  private function getPluginPath() {
    return 'libraries/tableresize';
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getPluginPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    if (!$editor->hasAssociatedFilterFormat()) {
      return FALSE;
    }

    // Automatically enable this plugin if the text format associated with this
    // text editor uses the filter_align or filter_caption filter and the
    // Table button is enabled.
    $format = $editor->getFilterFormat();
    if ($format->filters('filter_align')->status || $format->filters('filter_caption')->status) {
      $settings = $editor->getSettings();
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          foreach ($group['items'] as $button) {
            if ($button === 'Table') {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
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
  public function getConfig(Editor $editor) {
    return [];
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
  public function getButtons() {
    return [];
  }

}
