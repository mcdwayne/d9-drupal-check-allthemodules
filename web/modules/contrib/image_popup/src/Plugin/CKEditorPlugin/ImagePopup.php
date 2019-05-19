<?php

namespace Drupal\image_popup\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "imagepopup" plugin.
 *
 * @CKEditorPlugin(
 *   id = "imagepopup",
 *   label = @Translation("Image Popup"),
 *   module = "image_popup"
 * )
 */
class ImagePopup extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal( ) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'image_popup') . '/js/plugins/imagepopup/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'imagePopup_dialogTitleAdd' => t('Insert Image popup'),
      'imagePopup_dialogTitleEdit' => t('Edit Image popup'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'ImagePopup' => array(
        'label' => t('Image Popup'),
        'image' => drupal_get_path('module', 'image_popup') . '/js/plugins/imagepopup/icons/imagepopup.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $form_state->loadInclude('editor', 'admin.inc');
    $form['image_upload'] = editor_image_upload_settings_form($editor);
    $form['image_upload']['#attached']['library'][] = 'image_popup/image_popup.imagepopup.admin';
    $form['image_upload']['#element_validate'][] = array($this, 'validateImageUploadSettings');
    return $form;
  }

  /**
   * #element_validate handler for the "image_upload" element in settingsForm().
   *
   * Moves the text editor's image upload settings from the ImagePopup plugin's
   * own settings into $editor->image_upload.
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  function validateImageUploadSettings(array $element, FormStateInterface $form_state) {
    $settings = &$form_state->getValue(array('editor', 'settings', 'plugins', 'imagepopup', 'image_upload'));
    $form_state->get('editor')->setImageUploadSettings($settings);
    $form_state->unsetValue(array('editor', 'settings', 'plugins', 'imagepopup'));
  }

}
