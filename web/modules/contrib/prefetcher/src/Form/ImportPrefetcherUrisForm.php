<?php

namespace Drupal\prefetcher\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\prefetcher\PrefetcherImporterService;

/**
 * Class ImportPrefetcherUrisForm.
 *
 * @package Drupal\prefetcher\Form
 */
class ImportPrefetcherUrisForm extends FormBase {

  /**
   * Uploaded file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prefetcher_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['csv'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Import from .csv or .txt file'),
    );
    $form['csv']['delimiter'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('Add your delimiter (e.g., comma, pipe)'),
      '#maxlength' => 2,
      '#size' => 4,
      '#default_value' => ',',
    );
    $form['csv']['no_headers'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('No headers'),
      '#description' => $this->t('If your imported file does not include a header row, make sure that you check this box.'),
    );
    $form['csv']['override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override existing sources'),
      '#description' => $this->t('To override stored redirects, check this box.'),
    );
    $validators = array(
      'file_validate_extensions' => array('csv'),
      'file_validate_size' => array(file_upload_max_size()),
    );
    $form['csv']['csv_file'] = array(
      '#type' => 'file',
      '#title' => $this->t('CSV File'),
      '#description' => array(
        '#theme' => 'file_upload_help',
        '#description' => $this->t('CSV structure: URI, ENTITY_TYPE, ENTITY_ID'),
      ),
      '#upload_validators' => $validators,
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->file = file_save_upload('csv', $form['csv']['csv_file']['#upload_validators'], FALSE, 0);

    // Ensure we have the file uploaded.
    if (!$this->file) {
      $form_state->setErrorByName('csv_file', $this->t('You must add a valid file to the form in order to import prefetcher uris.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ini_set('auto_detect_line_endings', TRUE);
    // Don't do anything if no valid file.
    if (!isset($this->file)) {
      drupal_set_message($this->t('No valid file was found. No prefetcher uris have been imported.'), 'warning');
      return;
    }
    $options = [
      'status_code' => $form_state->getValue(array('advanced', 'status_code')),
      'override' => $form_state->getValue(array('csv', 'override')),
      'no_headers' => $form_state->getValue(array('csv', 'no_headers')),
      'delimiter' => $form_state->getValue(array('csv', 'delimiter')),
    ];
    // Call import service.
    PrefetcherImporterService::import($this->file, $options);
    // Remove file from Drupal managed files & from filesystem.
    file_delete($this->file->id());
  }

}
