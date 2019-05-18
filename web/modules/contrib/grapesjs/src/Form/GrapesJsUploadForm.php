<?php

namespace Drupal\grapesjs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Helper class for upload form.
 */
class GrapesJsUploadForm extends FormBase {

  /**
   * Helper function for get form id.
   */
  public function getFormId() {
    return 'grapesjs_upload_form';
  }

  /**
   * Helper function for buildForm.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['files'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://editor/grapesjs/',
      '#multiple' => TRUE,
      '#description' => t('Allowed extensions: gif png jpg jpeg'),
      '#upload_validators' => [
        'file_validate_is_image' => [],
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [25600000],
      ],
      '#title' => t('Upload Image'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do nothing as we are going around this form.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing as we are going around this form.
  }

}
