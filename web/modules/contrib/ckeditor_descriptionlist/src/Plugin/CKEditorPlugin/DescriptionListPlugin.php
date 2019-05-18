<?php

namespace Drupal\ckeditor_descriptionlist\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "descriptionlist" plugin.
 *
 * @CKEditorPlugin(
 *   id = "descriptionlist",
 *   label = @Translation("Description List")
 * )
 */
class DescriptionListPlugin extends CKEditorPluginBase {

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
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'DescriptionList' => array(
        'label' => t('Tag dl'),
        'image' => base_path() . 'libraries/descriptionlist/icons/descriptionlist.png',
      ),
      'DescriptionTerm' => array(
        'label' => t('Tag dd'),
        'image' => base_path() . 'libraries/descriptionlist/icons/descriptionterm.png',
      ),
      'descriptionValue' => array(
        'label' => t('Tag dt'),
        'image' => base_path() . 'libraries/descriptionlist/icons/descriptionvalue.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/descriptionlist/plugin.js';
  }

}
