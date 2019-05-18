<?php
/**
 * @file
 * This file contains the code for upload form of zip.
 */

namespace Drupal\pack_upload\Form;

use \Drupal\Core\Form\FormBase;

class UploadForm extends FormBase {

  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::getFormId()
   */
  public function getFormId() {
    return 'pack_upload_upload_form';
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::buildForm()
   */
  public function buildForm(array $form, array &$form_state) {
    $form['panel'] = array(
      '#title' => t('Bulk Media Uploader'),
      '#type' => 'fieldset',
    );
    $config = $this->config('pack_upload.settings');
    $path = file_build_uri($config->get('path'));

    $form['panel']['file'] = array(
      '#title' => t('Upload file'),
      '#type' => 'managed_file',
      '#upload_location' => $path,
      '#upload_validators' => array(
        'file_validate_extensions' => array('zip tar tar.gz'),
      ),
      '#description' => t('Create package of media files, for e.g., PDFs, images, text files and upload to Drupal. Valid extensions are .zip, .tar.gz, .tar. All files will be extracted to !directory', array('!directory' => $path)),
    );

    $form['panel']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }
  /**
   * {@inheritdoc}
   * @see \Drupal\Core\Form\FormInterface::submitForm()
   */
  public function submitForm(array &$form, array &$form_state) {

    $file = file_save_upload('file', $form_state, array(
      'file_validate_extensions' => array('zip tar tar.gz'),
    ));
    $uri = file_build_uri($this->config('pack_upload.settings')->get('path'));
    // Created a directory if not available.
    if (!is_dir($uri)) {
      drupal_mkdir($uri, FILE_CHMOD_DIRECTORY);
    }

    if ($file) {
      $file = is_array($file) ? array_shift($file) : $file;

      if ($path = file_unmanaged_move($file->getFileUri(), $uri, FILE_EXISTS_RENAME)) {
        $form_state['values']['file'] = $file;
        $realpath = drupal_realpath($path);
        $zip = new \ZipArchive();
        $zip->open($realpath);
        $result = $zip->extractTo(drupal_realpath($uri));

        if ($result === TRUE) {
          drupal_set_message(t('All media have been extracted to %path', array('%path' => drupal_realpath($uri))));
        }
        else {
          watchdog('error', 'There is some problem related to extraction of the file. Please upload and try again.', array(), WATCHDOG_ERROR, NULL);
          drupal_set_message(t('There is some problem related to extraction of the file. Please upload and try again.'), 'error', FALSE);
        }

        $zip->close();
      }
      else {
        $this->setFormError('file', $form_state, t("Failed to write the uploaded file the file folder."));
      }
    }
    else {
      $this->setFormError('file', $form_state, t('No file was uploaded.'));
    }
  }
}
