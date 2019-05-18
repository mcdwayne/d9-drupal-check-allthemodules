<?php

namespace Drupal\editor_image_attributes\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;

/**
 * Defines the "editorimageattributes" plugin.
 *
 * @CKEditorPlugin(
 *   id = "editorimageattributes",
 *   label = @Translation("Drupal editor image attributes"),
 *   module = "ckeditor"
 * )
 */
class EditorImageAttributes extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
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
  public function getFile() {
    return drupal_get_path('module', 'editor_image_attributes') . '/js/plugins/editor_image_attributes/plugin.js';
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
    if (!$editor->hasAssociatedFilterFormat()) {
      return FALSE;
    }

    // Automatically enable this plugin if the text format associated with this
    // text editor uses the filter_align or filter_caption filter and the
    // DrupalImage button is enabled.
    $format = $editor->getFilterFormat();
    if ($format->filters('filter_align')->status || $format->filters('filter_caption')->status) {
      $enabled = FALSE;
      $settings = $editor->getSettings();
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          foreach ($group['items'] as $button) {
            if ($button === 'DrupalImage') {
              $enabled = TRUE;
            }
          }
        }
      }
      return $enabled;
    }

    return FALSE;
  }

}
