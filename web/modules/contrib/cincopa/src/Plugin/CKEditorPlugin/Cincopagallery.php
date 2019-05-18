<?php

namespace Drupal\cincopa\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "cincopagallery" plugin.
 *
 * @CKEditorPlugin(
 *   id = "cincopagallery",
 *   label = @Translation("Cincopa New Gallery"),
 *   module = "cincopa"
 * )
 */
class Cincopagallery extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * isInternal - Return is this plugin internal or not.
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal( ) {
    return FALSE;
  }

  /**
   * getFile - Return absolute path to plugin.js file.
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'cincopa') . '/js/plugins/cincopagallery/plugin.js';
  }

  /**
   * getLibraries - Return dependency libraries, so those get loaded.
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * getConfig - Return configuration array
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

  /**
   * getButtons - Return button info.
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Cincopagallery' => array(
        'label' => t('Cincopa Gallery'),
        'image' => drupal_get_path('module', 'cincopa') . '/js/plugins/cincopagallery/icons/cincopagallery.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   * settingsForm - Defines form for plugin settings, where it can be configured in ‘admin/config/content/formats/manage/full_html’. For example, this form shown below is where we can set the max size, width and height of the image, against which image will be validated when uploading an image.
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return $form;
  }

}