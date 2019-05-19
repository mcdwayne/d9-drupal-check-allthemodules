<?php

/**
 * @file
 * Contains \Drupal\xwechat_material\Form\MaterialAddImageForm.
 */

namespace Drupal\xwechat_material\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

/**
 * Configure xwechat settings for this site.
 */
class MaterialAddImageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_add_image_material';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xwechat_config = NULL) {
    $validators = array(
      'file_validate_extensions' => array('png gif jpg jpeg'),
      'file_validate_size' => array(file_upload_max_size()),
    );
    $form['upload'] = array(
      '#type' => 'file',
      '#title' => $this->t('Upload file'),
      '#description' => array(
        '#theme' => 'file_upload_help',
        '#description' => $this->t('A xwechat materia image.'),
        '#upload_validators' => $validators,
      ),
      '#size' => 50,
      '#upload_validators' => $validators,
      '#attributes' => array('class' => array('file-import-input')),
    );
    $form['wid'] = array(
      '#type' => 'hidden',
      '#value' => $xwechat_config->wid,
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for a new uploaded favicon.
    $file = file_save_upload('upload', $form['upload']['#upload_validators'], NULL, 0, FILE_EXISTS_REPLACE);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('upload', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('upload', $this->t('The file could not be uploaded.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!empty($values['upload'])) {
      $filename = file_unmanaged_copy($values['upload']->getFileUri(), NULL, FILE_EXISTS_REPLACE);
      $values['material_path'] = $filename;
    }
    unset($values['upload']);

    if (!empty($values['material_path'])) {
      $values['material_path'] = $this->validatePath($values['material_path']);
    }

    if (!empty($values['material_path'])) {
      $wechat = new WeChat(xwechat_config_load($form_state->getValue('wid')));
      $wechat->getAccessToken();
      $url = $wechat->uploadImg(drupal_realpath($values['material_path']));
      drupal_set_message($url['url']);
    }

  }

  /**
   * Helper function for the system_theme_settings form.
   *
   * Attempts to validate normal system paths, paths relative to the public files
   * directory, or stream wrapper URIs. If the given path is any of the above,
   * returns a valid path or URI that the theme system can display.
   *
   * @param string $path
   *   A path relative to the Drupal root or to the public files directory, or
   *   a stream wrapper URI.
   * @return mixed
   *   A valid path that can be displayed through the theme system, or FALSE if
   *   the path could not be validated.
   */
  protected function validatePath($path) {
    // Absolute local file paths are invalid.
    if (drupal_realpath($path) == $path) {
      return FALSE;
    }
    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }
    // Prepend 'public://' for relative file paths within public filesystem.
    if (file_uri_scheme($path) === FALSE) {
      $path = 'public://' . $path;
    }
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

}

