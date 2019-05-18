<?php

namespace Drupal\ckeditor_bootstrap_buttons\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "btbutton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "btbutton",
 *   label = @Translation("CKEditor bootstrap button"),
 *   module = "ckeditor_bootstrap_buttons"
 * )
 */
class Btbutton extends CKEditorPluginBase {

  /**
   * Get path to library folder.
   */
  public function getLibraryPath() {
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      return libraries_get_path('btbutton');
    }
    else {
      return 'libraries/btbutton';
    }
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
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
      'btbutton' => [
        'label' => $this->t('Bootstrap Buttons'),
        'image' => $this->getLibraryPath() . '/icons/btbutton.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
