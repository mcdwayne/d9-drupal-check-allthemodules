<?php

namespace Drupal\glyphicons\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "glyphicons" plugin.
 *
 * @CKEditorPlugin(
 *   id = "glyphicons",
 *   label = @Translation("CKEditor Bootstrap Glyphicons"),
 * )
 */
class Glyphicons extends CKEditorPluginBase implements CKEditorPluginCssInterface {

  /**
   * Get path to library folder.
   */
  public static function getLibraryPath() {
    $path = '/libraries/glyphicons';
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('glyphicons', TRUE);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['colordialog'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'allowedContent' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      $this->getLibraryPath() . '/css/style.css',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Glyphicons' => [
        'label' => $this->t('Glyphicons'),
        'image' => $this->getLibraryPath() . '/icons/glyphicons.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'glyphicons/glyphicons',
    ];
  }

}
