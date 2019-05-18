<?php

namespace Drupal\file_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a form for editing files.
 */
class FileManagementEditFileForm extends FormBase {

  public function getFormId() {
    return 'file_management';
  }

  /**
   * {@inheritdoc}
   */
  function buildForm(array $form, FormStateInterface $form_state, FileInterface $file = NULL) {
    if (empty($file)) {
      // drupal_set_message
      // return to previous page or file overview page (use route)
    }

    $form['existing_file_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Existing file details'),
      '#open' => FALSE,
    ];

    $form['existing_file_details'] += \Drupal::service('file_management')->getFileInformation($file);

    $form['new_file_details'] = [
      '#type' => 'details',
      '#title' => $this->t('New file details'),
      '#open' => TRUE,
    ];

    $form['new_file_details']['old_fid'] = [
      '#type' => 'hidden',
      '#value' => $file->id(),
    ];

    $form['new_file_details']['new_file'] = [
      '#title' => $this->t('New file'),
      '#type' => 'file',
      '#description' => $this->t(
        'The new file to be used.<br />'
        . 'Leave empty to keep the existing file.<br />'
        . '<strong>Important:</strong> The filename will not be changed unless you specify a new filename below.'
      ),
    ];

    $form['new_file_details']['new_path'] = [
      '#title' => $this->t('New path'),
      '#type' => 'textfield',
      '#description' => $this->t(
        'The new path of the file.'
        . ' Please specify the full new path.<br />'
        . ' If no steam wrapper is defined, the existing one will be kept.<br />'
        . 'Leave empty to keep the existing file where it is.'
      ),
    ];

    $form['new_file_details']['new_filename'] = [
      '#title' => $this->t('New filename'),
      '#type' => 'textfield',
      '#description' => $this->t(
        'The new filename.<br />'
        . 'Leave empty to keep the existing filename.'
      ),
    ];

    $allowed_file_extensions = \Drupal::service('file_management')->getAllowedFileExtensions($file);
    if (!empty($allowed_file_extensions)) {
      $allowed_file_extensions = implode(' ', $allowed_file_extensions);

      $form['new_file_details']['new_filename']['#description'] .= '<br />'
        . $this->t('Allowed types: @extensions.', ['@extensions' => $allowed_file_extensions]);
    }

    $form['new_file_details']['actions']['#type'] = 'actions';
    $form['new_file_details']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    \Drupal::service('file_management')->addBackButton($form['new_file_details']['actions'], $this->t('Cancel'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $new_path = $form_state->getValue('new_path');
    $new_filename = $form_state->getValue('new_filename');

    if (strpos($new_path, '.') !== FALSE) {
      $form_state->setErrorByName('new_path', $this->t('You can not specify a filename in the new path.'));
    }

    if (strpos($new_filename, '\\') !== FALSE || strpos($new_filename, '/') !== FALSE) {
      $form_state->setErrorByName('new_filename', $this->t('You can not specify a path in the new filename.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Allowed extensions check. If not allowed, remove the file again!
    $old_fid = $form_state->getValue('old_fid');
    $old_file = \Drupal\file\Entity\File::load($old_fid);
    $old_uri = $old_file->getFileUri();
    $original_uri = $old_uri;
    $new_filename = $old_file->getFilename();
    $new_uri = $old_file->getFileUri();

    // Check if we have to set a new filename.
    if (!empty($form_state->getValue('new_filename'))) {
      $new_filename = $form_state->getValue('new_filename');
      $new_uri = str_replace($old_file->getFilename(), $new_filename, $new_uri);
    }

    // Check if we have to move the file.
    if (!empty($form_state->getValue('new_path'))) {
      $new_url = parse_url($form_state->getValue('new_path'));
      $old_url = parse_url($old_uri);
      $new_scheme = $old_url['scheme'];
      $new_path = trim($new_url['path'], "\\/");

      if (array_key_exists('scheme', $new_url)) {
        $new_scheme = $new_url['scheme'];
      }

      $new_folder_uri = $new_scheme . '://' . $new_path;
      $new_uri = $new_folder_uri . '/' . $new_filename;

      if (file_prepare_directory($new_folder_uri, FILE_CREATE_DIRECTORY)) {
        // TODO: Move the file.
      }
      else {
        // TODO: Could not create the needed directory, ABORT!
      }
    }

    // Check if we have to replace the file.
    // TODO: set file_validate_extensions correctly
    // TODO: Also check the return value of file_save_upload()[0] (could be FALSE)
    $new_file = file_save_upload('new_file', ['file_validate_extensions' => []]);
    if (!empty($new_file) && is_array($new_file)) {
      $new_file = $new_file[0];
      $old_uri = $new_file->getFileUri();
      $old_file->setMimeType($new_file->getMimeType());
      $old_file->setSize($new_file->getSize());

      // Delete the old file.
      file_unmanaged_delete($original_uri);

      // Move the new file to where the old file was.
      // TODO: We should use file_unmanaged_copy() so delete() doesn't throw a warning.
      file_unmanaged_move($old_uri, $original_uri, FILE_EXISTS_REPLACE);
    }

    if ($original_uri !== $new_uri) {
      // TODO: Check if file exists - if so, abort mission [unless it's our file]
      // Move the temporary uploaded file to the new location.
      // TODO: We should use file_unmanaged_copy()
      // So delete() doesn't throw a warning.
      file_unmanaged_move($original_uri, $new_uri, FILE_EXISTS_REPLACE);
    }

    // Delete the db entry of the new file, we've already adapted the old file.
    if (!empty($new_file)) {
      $new_file->delete();
    }

    // Update file details.
    $old_file->setFilename($new_filename);
    $old_file->setFileUri($new_uri);
    $old_file->save();

    if (!empty($new_file) && is_array($new_file)) {
      // Delete the old file physically.
      // this should be after the move happened AND was successful.
      // TODO: don't remove if old_original_uri == new_original_uri.
      file_unmanaged_delete($old_uri); 
    }

    // Flush image styles.
    // TODO: Flush original file.
    $image_styles = ImageStyle::loadMultiple();
    foreach ($image_styles as $image_style) {
      $image_style->flush($new_uri);
    }

    $messenger = \Drupal::messenger();
    $messenger->addMessage($this->t('@type %title has been updated.', [
      '@type' => $this->t('File'),
      '%title' => $old_file->label(),
    ]));
  }

}
