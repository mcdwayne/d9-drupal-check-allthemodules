<?php

namespace Drupal\ckeditor_texttransform\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Text Transform" plugin.
 *
 * @CKEditorPlugin(
 *   id = "texttransform",
 *   label = @Translation("Text Transform")
 * )
 */
class CkeditorTextTransform extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Returns texttransform plugin path relative to drupal root.
   *
   * @return string
   *   Relative path to the plugin folder.
   */
  protected function getPluginPath() {
    return 'libraries/texttransform';
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
  public function getLibraries(Editor $editor) {
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
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'TransformTextSwitcher' => [
        'label' => t('Transform Text Switcher'),
        'image' => $this->getPluginPath() . '/images/transformSwitcher.png',
      ],
      'TransformTextToUppercase' => [
        'label' => t('Transform Text to Uppercase'),
        'image' => $this->getPluginPath() . '/images/transformToUpper.png',
      ],
      'TransformTextToLowercase' => [
        'label' => t('Transform Text to Lowercase'),
        'image' => $this->getPluginPath() . '/images/transformToLower.png',
      ],
      'TransformTextCapitalize' => [
        'label' => t('Capitalize Text'),
        'image' => $this->getPluginPath() . '/images/transformCapitalize.png',
      ],
    ];
  }

}
