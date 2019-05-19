<?php

/**
 * @file
 * Contains \Drupal\ueditor\Form\UeditorGlobalSettingsForm.
 */

namespace Drupal\ueditor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

  // Config form is a classic form like any other : see form generators for more information.

class UeditorGlobalSettingsForm extends ConfigFormBase {

  /*
  **
  * Returns a unique string identifying the form.
  *
  * @return string
  *   The unique string identifying the form.
  */
  public function getFormId() {
    return 'ueditor_global_settings';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */

  public function buildForm(array $form, FormStateInterface $form_state) {
    // This is how you call a $config object to get all the settings of your module calling module_name.settings.
    $config = $this->config('ueditor.settings');

    $form['global']['highlighting_format'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Highlighting Code Format'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_highlighting_format'),
        '#description' => t('If enabled, when you insert code, the code will highlighting.'),
    );
    $form['global']['enable_formula_editor'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Formula in ueditor'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_enable_formula_editor'),
        '#description' => $this->t('If you want to use KityFormula in the ueditor, you must install the @kityformula_link library at first. and the place should be like this: <i>/modules/ueditor/lib/kityformula-plugin/kityformula/js/kityformula-editor.all.min.js</i>', [
            '@kityformula_link' => Link::fromTextAndUrl(t('KityFormula'), Url::fromUri('http://ueditor.baidu.com/download/kityformula-plugin.zip'))->toString(),
        ])
    );

    //ueditor watermark support
    $form['global']['watermark'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Use watermark when upload'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark'),
        '#tree' => FALSE,
        '#description' => $this->t('Check here if you want use watermark when upload.')
    );
    $form['global']['watermark_settings'] = array(
        '#type' => 'container',
        '#states' => array(
            'invisible' => array(
                'input[name="watermark"]' => array('checked' => FALSE),
            ),
        ),
    );
    $form['global']['watermark_settings']['watermark_type'] = array(
        '#type' => 'select',
        '#title' => 'The type of watermark',
        '#options' => array(
            'image' => 'Image',
            'text' => 'Text',
        ),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_type'),
    );
    $form['global']['watermark_settings']['watermark_image'] = array(
        '#type' => 'container',
        '#states' => array(
            'invisible' => array(
                '#edit-watermark-type' => array('value' => 'text'),
            ),
        ),
    );
    $form['global']['watermark_settings']['watermark_image']['watermark_path'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Path to custom watermark'),
        '#description' => $this->t('The path to the file you would like to use as watermark image.'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_path'),
    );
    $validators = array(
      'file_validate_extensions' => array('jpg png'),
      'file_validate_size' => array(file_upload_max_size()),
    );
    $form['global']['watermark_settings']['watermark_image']['watermark_upload'] = array(
        '#type' => 'file',
        '#title' => $this->t('Upload watermark image'),
        '#size' => 50,
        '#upload_validators' => $validators,
        '#description' => $this->t("If you don't have direct file access to the server, use this field to upload watermark image.only support : jpg png")
    );
    $form['global']['watermark_settings']['watermark_image']['watermark_alpha'] = array(
        '#type' => 'select',
        '#title' => $this->t('Watermark Alpha'),
        '#options' => array_combine(array(30,50,80,100), array(30,50,80,100)),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_alpha'),
    );
    $form['global']['watermark_settings']['watermark_text'] = array(
        '#type' => 'container',
        '#states' => array(
            'invisible' => array(
                '#edit-watermark-type' => array('value' => 'image'),
            ),
        ),
    );
    $form['global']['watermark_settings']['watermark_text']['watermark_textcontent'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Watermark Text'),
        '#description' => $this->t('The path to the file you would like to use as watermark image.'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_textcontent'),
    );
    $form['global']['watermark_settings']['watermark_text']['watermark_textfontsize'] = array(
        '#type' => 'select',
        '#title' => $this->t('Watermark Text Font Size'),
        '#options' => array_combine(array(12,16,18,24,36,48,60), array(12,16,18,24,36,48,60)),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_textfontsize'),
    );
    $form['global']['watermark_settings']['watermark_text']['watermark_textcolor'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Watermark Text Color'),
        '#description' => $this->t('The path to the file you would like to use as watermark image.'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_textcolor'),
    );
    $form['global']['watermark_settings']['watermark_place'] = array(
        '#type' => 'select',
        '#title' => $this->t('Watermark Place'),
        '#options' => array(
            '0' => 'Random',
            '1' => 'Top Left',
            '2' => 'Top Center',
            '3' => 'Top Right',
            '4' => 'Middle Left',
            '5' => 'Middle Center',
            '6' => 'Middle Right',
            '7' => 'Bottom Left',
            '8' => 'Bottom Center',
            '9' => 'Bottom Right'
        ),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_watermark_place'),
    );
    
    //file/image/video upload max size
    $form['maxsize']['image_size'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Image upload size'),
        '#description' => $this->t('The maxsize of upload image. unit: KB'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_image_maxsize'),
    );

    $form['maxsize']['file_size'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('File upload size'),
        '#description' => $this->t('The maxsize of upload file. unit: KB'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_file_maxsize'),
    );

    $form['maxsize']['video_size'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Video upload size'),
        '#description' => $this->t('The maxsize of upload video. unit: KB'),
        '#default_value' => $config->get('ueditor_global_settings.ueditor_video_maxsize'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for a new uploaded favicon.
    $file = file_save_upload('watermark_upload', $form['global']['watermark_settings']['watermark_image']['watermark_upload']['#upload_validators'], NULL, 0, FILE_EXISTS_REPLACE);
    if (isset($file)) {
      // File attachment was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('watermark_upload', $file);
      }
      else {
        // File attachment failed.
        $form_state->setErrorByName('watermark_upload', $this->t('The watermark could not be uploaded.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set & save the configuration : get the $config object.
    $config = $this->config('ueditor.settings');
    $values = $form_state->getValues();

    if(file_exists('modules/ueditor/lib/kityformula-plugin/kityformula/js/kityformula-editor.all.min.js')) {
      $kityformula_installed = TRUE;
    }
    else {
      $kityformula_installed = FALSE;
    }

    if($values['enable_formula_editor'] == 1){
      if(!$kityformula_installed){
        drupal_set_message(t('Please install the @kityformula_link library at first, and the place should be like this: <i>modules/ueditor/lib/kityformula-plugin/kityformula/js/kityformula-editor.all.min.js</i>', [
            '@kityformula_link' => Link::fromTextAndUrl(t('KityFormula'), Url::fromUri('http://ueditor.baidu.com/download/kityformula-plugin.zip'))->toString(),
        ]), 'error');
      }else{        
        $config->set('ueditor_global_settings.ueditor_enable_formula_editor', $values['enable_formula_editor']);
      }
    }



    if (!empty($values['watermark_upload'])) {
      $filename = file_unmanaged_copy($values['watermark_upload']->getFileUri(), NULL, FILE_EXISTS_REPLACE);
      $values['watermark_path'] = $filename;
    }
    unset($values['watermark_upload']);

    if (!empty($values['watermark_path'])) {
      $values['watermark_path'] = $this->validatePath($values['watermark_path']);
    }

    $config->set('ueditor_global_settings.ueditor_highlighting_format', $values['highlighting_format']);
    $config->set('ueditor_global_settings.ueditor_watermark', $values['watermark']);
    $config->set('ueditor_global_settings.ueditor_watermark_type', $values['watermark_type']);
    $config->set('ueditor_global_settings.ueditor_watermark_path', $values['watermark_path']);
    $config->set('ueditor_global_settings.ueditor_watermark_alpha', $values['watermark_alpha']);
    $config->set('ueditor_global_settings.ueditor_watermark_textcontent', $values['watermark_textcontent']);
    $config->set('ueditor_global_settings.ueditor_watermark_textfontsize', $values['watermark_textfontsize']);
    $config->set('ueditor_global_settings.ueditor_watermark_textcolor', $values['watermark_textcolor']);
    $config->set('ueditor_global_settings.ueditor_watermark_place', $values['watermark_place']);
    $config->set('ueditor_global_settings.ueditor_image_maxsize', $values['image_size']);
    $config->set('ueditor_global_settings.ueditor_file_maxsize', $values['file_size']);
    $config->set('ueditor_global_settings.ueditor_video_maxsize', $values['video_size']);
    $config->save();
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

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['ueditor.settings'];
  }

}