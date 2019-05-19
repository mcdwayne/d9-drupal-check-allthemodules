<?php

namespace Drupal\webform_dropzonejs\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a 'webform_dropzonejs' element.
 *
 * @WebformElement(
 *   id = "webform_dropzonejs",
 *   label = @Translation("DropzoneJS"),
 *   description = @Translation("Provides a form element for uploading and saving a file via dropzonejs."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class WebformDropzonejs extends WebformManagedFileBase {

  /**
   * {@inheritdoc}
   * 
   * This is the admin form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Hide the options that are not relevant.
    $form['file']['button']['#access'] = FALSE;
    $form['file']['button__title']['#access'] = FALSE;
    $form['file']['button__attributes']['#access'] = FALSE;
    $form['file']['file_placeholder']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateManagedFile(array &$element, FormStateInterface $form_state, &$complete_form) {
    // If this field is being loaded with existing files, we need to make sure
    // those files are attached to the field if they were not deleted.
    $fids = isset($element['#default_value']) ? $element['#default_value'] : [];
    $files = [];
    
    if (!empty($fids)) {
      $check_if_deleted = isset($_POST['deleted_dropzone_files']) ? TRUE : FALSE;
      foreach ($fids as $file_key => $fid) {
        if ($check_if_deleted && in_array($fid, $_POST['deleted_dropzone_files'])) {
          // This file was attached to this submission, but now it should be 
          // deleted.
          file_delete($fid);
          unset($fids[$file_key]);
          continue;
        }

        // Load the file object.
        $files[] = File::load($fid);
      }
    }

    // Handle newly uploaded files.
    if (isset($element['#value']['uploaded_files']) && isset($element['#upload_location'])) {

      // Make the temporary uploaded file a permanent file and move it to a
      // location where the webform code can process it.
      $destination_path = $element['#upload_location'];
      foreach ($element['#value']['uploaded_files'] as $dropzone_file) {
        $file_name = $destination_path . '/' . $dropzone_file['filename'];

        // Save the file as a permanent file.
        if ($file_data = file_get_contents($dropzone_file['path'])) {
          if ($final_file = file_save_data($file_data, $file_name, FILE_EXISTS_RENAME)) {
            $fid = $final_file->id();
            $files[] = $final_file;
            $fids[] = $fid;

            // Delete the temporary file.
            unlink($dropzone_file['path']);
          }
        }
      }

    }

    $element['#files'] = $files;

    // Save the file(s) to this field.
    if (!empty($files)) {
      if (empty($element['#multiple'])) {
        $form_state->setValueForElement($element, reset($fids));
      }
      else {
        $form_state->setValueForElement($element, $fids);
      }
    }
    else {
      $form_state->setValueForElement($element, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileExtensions(array $element = NULL) {
    if (!empty($element['#file_extensions'])) {
      return $element['#file_extensions'];
    }

    // By default, use the "webform.settings.default_managed_file_extensions"
    // config value.
    return $this->configFactory->get('webform.settings')->get('file.default_managed_file_extensions');
  }

}
