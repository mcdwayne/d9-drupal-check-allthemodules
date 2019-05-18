<?php

namespace Drupal\csv_to_config\Form;

use Drupal\Core\Form\FormStateInterface;

class CSVImportStep1Form extends MultistepFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csv_to_config_import_form_step1';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['heading'] = array(
      '#markup' => '<h2>' . $this->t('Step 1 of 3') . '</h2>',
    );

    $form['csv_file'] = array(
      '#title' => $this->t('Upload a CSV File'),
      '#type' => 'file',
      '#description' => $this->t('Select the CSV file that you want to convert to configuration.'),
    );

    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $formValues = &$form_state->getValues();
    $formValues['file_contents'] = '';

    $validators = array('file_validate_extensions' => array('csv'));
    if ($file = file_save_upload('csv_file', $validators, FALSE, 0, FILE_EXISTS_REPLACE)) {
      $csv_file = fopen($file->getFileUri(), 'r+');

      // Read CSV rows.
      $csvArray = array();
      while( $csvRow = fgetcsv($csv_file) ) {
        $key = array_shift($csvRow);
        $csvArray[$key] = $csvRow;
      }

      fclose($csv_file);
      $file->delete();

      if (empty($csvArray)) {
        drupal_set_message($this->t('The verification file import failed, because the file %filename could not be read.', array('%filename' => $file->getFilename())), 'error');
      }
      else {
        $formValues['file'] = $file->getFilename();
        $formValues['csv_array'] = $csvArray;
      }
    }
    else {
      drupal_set_message($this->t('You need to upload a CSV file.'), 'error');
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('file', $form_state->getValue('file'));
    $this->store->set('csv_array', $form_state->getValue('csv_array'));
    $form_state->setRedirect('csv_to_config.csv_import.step2');
  }

}
