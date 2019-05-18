<?php

namespace Drupal\bulk_delete\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class tableform.
 *
 * @package Drupal\bulk_delete_301\Form
 */
class bulk_delete_301 extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_delete_301_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  $form['csv'] = array(
    '#type' => 'fieldset',
    '#title' => t('Import from .csv or .txt file'),
    '#description' => t('To bulk delete 301 redirects, you must create a CSV or TXT.'),
  );

  $form['csv']['delimiter'] = array(
    '#type' => 'textfield',
    '#title' => t('Delimiter'),
    '#description' => t('Add your delimiter.'),
    '#default_value' => ',',
    '#maxlength' => 2,
  );
  $form['csv']['no_headers'] = array(
    '#type' => 'checkbox',
    '#title' => t('No headers'),
    '#description' =>
    t('Check if the imported file does not start with a header row.'),
  );

  $form['csv']['check_node'] = array(
    '#type' => 'checkbox',
    '#title' => t('Delete 301 if node exists'),
    '#description' => t('Check to not delete the 301 redirect url until node exist for url.'),
  );

  $form['csv']['csv_file'] = array(
    '#type' => 'file',
    '#description' =>
    t('The CSV file must include only one column in the format:
      "URL".'),
  );

  $form['submit'] = array('#type' => 'submit', '#value' => t('Import'));
  $form['#attributes'] = array('enctype' => "multipart/form-data");
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  $validators = array('file_validate_extensions' => array('csv txt'));
  if ($file = file_save_upload('csv_file', $validators)) {
    $form_state['uploaded_file'] = $file;
  }
  else {
    form_set_error('form', t('File upload failed.'));
  }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  if (!isset($form_state['uploaded_file'])) {
    return;
  }
  bulk_delete_301_processing_file(
      $form_state['uploaded_file']->uri, $form_state['values']);
  file_delete($form_state['uploaded_file'])
  }

}
