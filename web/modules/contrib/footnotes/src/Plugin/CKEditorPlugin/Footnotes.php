<?php

namespace Drupal\footnotes\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "CodeButton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "footnotes",
 *   label = @Translation("FootnotesButton")
 * )
 */
class Footnotes extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * Implements CKEditorPluginInterface::getDependencies().
   */
  public function getDependencies(Editor $editor) {
    return ['fakeobjects'];
  }

  /**
   * Implements CKEditorPluginInterface::getLibraries().
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * Implements CKEditorPluginInterface::isInternal().
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Implements CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return drupal_get_path('module', 'footnotes') . '/assets/js/ckeditor/plugin.js';
  }

  /**
   * Implements CKEditorPluginButtonsInterface::getButtons().
   */
  public function getButtons() {
    return [
      'footnotes' => [
        'label' => t('Footnotes'),
        'image' => drupal_get_path('module', 'footnotes') . '/assets/js/ckeditor/icons/footnotes.png',
      ],
    ];
  }

  /**
   * Implements CKEditorPluginInterface::getConfig().
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
