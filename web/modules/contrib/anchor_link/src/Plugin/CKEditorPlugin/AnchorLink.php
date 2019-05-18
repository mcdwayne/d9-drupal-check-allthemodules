<?php

namespace Drupal\anchor_link\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "link" plugin.
 *
 * @CKEditorPlugin(
 *   id = "link",
 *   label = @Translation("CKEditor Web link"),
 *   module = "anchor_link"
 * )
 */
class AnchorLink extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [
      'fakeobjects',
    ];
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
    $path = $this->getLibraryPath();

    return [
      'Link' => [
        'label' => t('Link'),
        'image' => $path . '/icons/link.png',
      ],
      'Unlink' => [
        'label' => t('Unlink'),
        'image' => $path . '/icons/unlink.png',
      ],
      'Anchor' => [
        'label' => t('Anchor'),
        'image' => $path . '/icons/anchor.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * Get the CKEditor Link library path.
   *
   * @return string
   *   The library path with support for the Libraries API module.
   */
  protected function getLibraryPath() {
    // Support for "Libraries API" module.
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      return libraries_get_path('link');
    }

    return 'libraries/link';
  }

}
