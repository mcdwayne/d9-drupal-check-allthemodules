<?php

namespace Drupal\boxout\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Boxout" plugin.
 *
 * @CKEditorPlugin(
 *   id = "boxout",
 *   label = @Translation("Boxout"),
 *   module = "boxout"
 * )
 */
class Boxout extends CKEditorPluginBase {
  /**
   * Gets a path to module.
   *
   * @return string
   *   Full path to module.
   */
  private function path() {
    return drupal_get_path('module', 'boxout');
  }

  /**
   * Implements CKEditorPluginInterface::getDependencies().
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * Implements CKEditorPluginInterface::getLibraries().
   */
  public function getLibraries(Editor $editor) {
    return array();
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
    return $this->path() . "/js/plugins/boxout/plugin.js";
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'boxout_dialog_title_insert' => $this->t('Insert Boxout'),
    );
  }

  /**
   * Implements CKEditorPluginButtonsInterface::getButtons().
   */
  public function getButtons() {
    return array(
      'Boxout' => array(
        'label' => $this->t('Boxout'),
        'image' => $this->path() . '/js/plugins/boxout/boxout.png',
      ),
    );
  }

}
