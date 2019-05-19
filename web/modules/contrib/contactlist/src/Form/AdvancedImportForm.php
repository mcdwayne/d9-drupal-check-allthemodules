<?php

namespace Drupal\contactlist\Form;

use Alma\CsvTools\CsvDataListMapper;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Form\FormStateInterface;

class AdvancedImportForm extends ImportFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contactlist_advanced_import_form';
  }

  /**
   * Bulk contact import form from csv files.
   *
   * @todo Add better help in this form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = array(
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#title' => $this->t('Advanced Contact list import'),
        '#prefix' => 'Use this utility to import contact lists from a CSV (comma-separated values) or TSV (tab-separated values) format file. Compatible with rows copied or exported from any spreadsheet application.',
      );
    $form['import']['csv_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Contact list entries'),
      '#columns' => 38,
      '#rows' => 5,
      '#required' => TRUE,
      '#description' => $this->t('Copy the list of contact lists from excel or word (comma- or tab-delimited) and paste the text here.'),
      '#prefix' => '<hr/>',
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Normalize the submitted CSV text into an importable data structure.
    // Replace tabs with commas to facilitate direct copy-pasting from
    // excel-style spreadsheets.
    if ($csv_text = $this->cleanCsvFreeText($form_state->getValue('csv_text'))) {
      $data = explode("\n", $csv_text, 2);
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
        ->setSourceText($csv_text)
        ->setDataMap($mapping)
        ->setSkipEmptyRows(TRUE);

      $form_state->setValue('import', $import);
    }

  }

}
