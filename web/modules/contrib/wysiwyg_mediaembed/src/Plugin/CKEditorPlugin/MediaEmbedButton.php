<?php

namespace Drupal\wysiwyg_mediaembed\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "wysiwyg_mediaembed" plugin.
 *
 * @CKEditorPlugin(
 *   id = "mediaembed",
 *   label = @Translation("CKEditor Media Embed Button")
 * )
 */
class MediaEmbedButton extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * Get path to library folder.
   */
  public function getLibraryPath() {
    $path =  base_path() . 'libraries/mediaembed';
    return $path;
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
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
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
    $path = $this->getLibraryPath();
    return [
      'MediaEmbed' => [
        'label' => $this->t('Media Embed'),
        'image' => $path . '/icons/mediaembed.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return [];
  }

}
