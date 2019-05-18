<?php

namespace Drupal\color_page_break\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Page Break" plugin.
 *
 * @CKEditorPlugin(
 *   id = "pagebreak",
 *   label = @Translation("Page Break"),
 *   module = "color_page_break"
 * )
 */
class PageBreak extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'color_page_break') . '/js/plugins/pagebreak/plugin.js';
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
    $iconImage = drupal_get_path('module', 'color_page_break') . '/js/plugins/pagebreak/images/plugicon.png';
    return [
      'PageBreakButton' => [
        'label' => t('Add page break'),
        'image' => $iconImage,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
