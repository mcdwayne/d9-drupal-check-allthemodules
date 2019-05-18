<?php

namespace Drupal\nbsp\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;

/**
 * Defines the "NBSP" plugin.
 *
 * Plugin to insert a non-breaking space (&nbsp;) into the content
 * by pressing Ctrl+Space or using the provided button.
 *
 * @CKEditorPlugin(
 *   id = "nbsp",
 *   label = @Translation("Non-breaking space"),
 *   module = "nbsp"
 * )
 */
class Nbsp extends CKEditorPluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
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
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'nbsp') . '/plugins/' . $this->getPluginId() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'DrupalNbsp' => [
        'label' => $this->t('Non-breaking space'),
        'image' => drupal_get_path('module', 'nbsp') . '/plugins/' . $this->getPluginId() . '/icons/' . $this->getPluginId() . '.png',
      ],
    ];
  }

}
