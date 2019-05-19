<?php

namespace Drupal\webform_dropzonejs\Element;

use Drupal\webform\Element\WebformManagedFileBase;
use Drupal\dropzonejs\Element\DropzoneJs;
use Drupal\file\Entity\File;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element for a 'dropzonejs' element.
 *
 * @FormElement("webform_dropzonejs")
 */
class WebformDropzonejs extends DropzoneJs {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [[$class, 'processDropzoneJs']],
      '#pre_render' => [[$class, 'preRenderDropzoneJs']],
      '#theme' => 'dropzonejs',
      '#theme_wrappers' => ['form_element'],
      '#tree' => TRUE,
      '#attached' => [
        'library' => ['dropzonejs/integration'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderDropzoneJs(array $element) {
    // Grab the maximum number of files allowed. This is based on the 
    // $element['#multiple'] value:
    // - When this value is not set, only allow one.
    // - When this value equals TRUE, allow unlimited.
    // - Otherwise $element['#multiple'] equals the number they can upload.
    $max_files = 1;
    if (isset($element['#multiple'])) {
      if ($element['#multiple'] === TRUE) {
        $max_files = NULL;
      }
      else {
        $max_files = (int) $element['#multiple'];
      }
    }

    $element['#dropzone_description'] = t('Drop files here to upload them');
    $element['#extensions'] = isset($element['#upload_validators']['file_validate_extensions'][0]) ? $element['#upload_validators']['file_validate_extensions'][0] : '';
    $element['#max_files'] = $max_files;
    $element['#max_filesize'] = !empty($element['#max_filesize']) ? $element['#max_filesize'] . 'M' : '';
    $element = parent::preRenderDropzoneJs($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDropzoneJs(&$element, FormStateInterface $form_state, &$complete_form) {
    $files = [];
    $element_id = $element['#id'];

    // Load our JS so we can tweak dropzoneJS and pre-load data.
    $element['#attached']['library'][] = 'webform_dropzonejs/integration';

    // Add already uploaded files to this dropzonejs field.
    if (!empty($element['#default_value'])) {
      // Put together the data to send to the JS.
      foreach ($element['#default_value'] as $fid) {
        if ($file = File::load($fid)) {
          // Is this file an image?
          $is_image = FALSE;
          switch($file->getMimeType()) {
            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
              $is_image = TRUE;
              break;            
          }

          $files[] = [
            'id' => $file->id(),
            'path' => $file->url(),
            'name' => $file->getFilename(),
            'size' => $file->getSize(),
            'accepted' => TRUE,
            'is_image' => $is_image,
          ];
        }
      }
    }

    // Send the uploaded files to a JS variable.
    $element['#attached']['drupalSettings']['webformDropzoneJs'][$element_id]['files'] = $files;

    // Define a variable where the files will be uploaded to make it easier
    // to link to them in the JS.
    $element['#attached']['drupalSettings']['webformDropzoneJs'][$element_id]['file_directory'] = str_replace(
      array('private://', '_sid_'), 
      array('/system/files/', $element['#webform_submission']), 
      $element['#upload_location']
    );

    // Call the parent method.
    parent::processDropzoneJs($element, $form_state, $complete_form);

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformDropzonejs']);

    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_dropzonejs'.
   */
  public static function validateWebformDropzonejs(&$element, FormStateInterface $form_state) {
    if ($element['#required'] && empty($element['#value']['uploaded_files'])) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $return['uploaded_files'] = [];

    if ($input !== FALSE) {
      $user_input = NestedArray::getValue($form_state->getUserInput(), $element['#parents'] + ['uploaded_files']);

      if (!empty($user_input['uploaded_files'])) {
        $file_names = array_filter(explode(';', $user_input['uploaded_files']));
        $tmp_upload_scheme = \Drupal::configFactory()->get('dropzonejs.settings')->get('tmp_upload_scheme');

        foreach ($file_names as $name) { 
          // The upload handler appended the txt extension to the file for
          // security reasons. We will remove it in this callback.
          $old_filepath = $tmp_upload_scheme . '://' . $name;
          
          // The upload handler appended the txt extension to the file for
          // security reasons. Because here we know the acceptable extensions
          // we can remove that extension and sanitize the filename.
          $name = self::fixTmpFilename($name);
          $name = file_munge_filename($name, self::getValidExtensions($element));

          // Create the correct file extension path.
          $new_filepath = $tmp_upload_scheme . '://' . $name;

          if (file_exists($old_filepath)) {
            if (file_exists($new_filepath)) {
              unlink($new_filepath);
            }

            @rename($old_filepath, $new_filepath);

            $return['uploaded_files'][] = [
              'path' => $new_filepath,
              'filename' => $name,
            ];
          }          
        }

        $form_state->setValueForElement($element, $return);
      }
      
    }
    return $return;
  }
}
