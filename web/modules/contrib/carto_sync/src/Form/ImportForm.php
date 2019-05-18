<?php

namespace Drupal\carto_sync\Form;

use Drupal\Core\Form\FormStateInterface;

class ImportForm extends CartoSyncConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'carto_sync_import';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to create the dataset %dataset?', ['%dataset' => $this->dataset]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Views data will be imported into a CARTO dataset');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Import');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->cartoSyncApi->datasetExists($this->dataset)) {
      $form_state->setError($form['actions']['submit'], $this->t('Dataset @dataset already exists in your CARTO account.', ['@dataset' => $this->dataset]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $executable = $this->view->getExecutable();
    $imported = $executable->executeDisplay($this->displayId);
    if ($imported) {
      drupal_set_message($this->t('Data synchronized with CARTO successfully.'));
    }
    else {
      drupal_set_message($this->t('There was an error processing your request. More information may be available in the system logs.'), 'error');
    }
    $form_state->setRedirect('carto_sync.carto_sync_dashboard');
  }

}
