<?php

namespace Drupal\uikitty_buttons\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "ukbutton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ukbutton",
 *   label = @Translation("Uikitty buttons"),
 *   module = "uikitty_buttons"
 * )
 */
class ukbutton extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return base_path() .'/libraries/ukbutton/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
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
    return array(
      'ukbutton' => array(
        'label' => t('Uikitty Buttons'),
        'image' => '/libraries/ukbutton/icons/ukbutton.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
