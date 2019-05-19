<?php

namespace Drupal\contactlist\Form;

use Alma\CsvTools\CsvDataListMapper;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Form\FormStateInterface;

class BulkImportForm extends ImportFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contactlist_bulk_import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Bulk Contact list import'),
        '#collapsible' => TRUE,
        '#prefix' => 'Use this utility to import a huge number (> 1000) of contact lists from a CSV (comma-separated values) or TSV (tab-separated values) format file. '
          . 'Only csv or tab-separated format is supported. Even when the extension is .csv, .txt or something else, the format MUST be CSV',
      );
    $form['import']['csv_file'] = array(
      '#type' => 'file',
      '#title' => $this->t('CSV file'),
      '#size' => 40,
      '#description' => $this->t('Select a csv or tab-delimited file to be uploaded.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for non-previewed upload. Update both the stored CSV and the
    // column map in this case.
    $validators = array(
      'file_validate_extensions' => array('csv txt'),
      // 5MB file size limit.
      'file_validate_size' => array(5 * 1024 * 1024),
    );
    /** @var \Drupal\file\Entity\File[] $files */
    $files = file_save_upload('csv_file', $validators, FALSE, NULL, FILE_EXISTS_REPLACE);
    if ($files[0] && $files[0]->getFilename()) {
      // Read only 512bytes for file previews to limit memory usage.
      $csv_file_content_preview = $this->cleanCsvFreeText(file_get_contents($files[0]->getFileUri(), NULL, NULL, NULL, 512));
      $data = explode("\n", $csv_file_content_preview, 2);
      $mapping = $this->getHeaderMapping(explode(',', $data[0]));
      // Validate required fields and default field.
      // Create a dummy contact list entry to get field meta-info.
      $dummy = ContactListEntry::create();
      foreach ($this->getContactEntryFields() as $field_name => $field) {
        // Set error for required fields with no default value that have not been imported.
        if ($field->isRequired() && !$field->getDefaultValue($dummy) && !array_key_exists($field_name, $mapping)) {
          $form_state->setErrorByName('import', $this->t('Missing required field: @field', array('@field' => $field_name)));
        }
      }
      $import = (new CsvDataListMapper())
        ->setHasHeader((bool)$form_state->getValue('has_header'))
        ->setSourceFile($files[0]->getFileUri())
        ->setDataMap($mapping)
        ->setSkipEmptyRows(TRUE);

      $form_state->setValue('import', $import);
    }
    else {
      $form_state->setErrorByName('csv_file', $this->t('CSV file not specified or found.'));
    }

  }

}
