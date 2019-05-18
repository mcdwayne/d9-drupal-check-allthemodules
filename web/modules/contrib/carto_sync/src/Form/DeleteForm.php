<?php

namespace Drupal\carto_sync\Form;

use Drupal\Core\Form\FormStateInterface;

class DeleteForm extends CartoSyncConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'carto_sync_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the dataset %id?', ['%id' => $this->displayId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('You will delete the dataset from your CARTO account. This action cannot be undone');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->cartoSyncApi->datasetExists($this->dataset)) {
      $form_state->setError($form['actions']['submit'], $this->t('Dataset @dataset does not exist in your CARTO account.', ['@dataset' => $this->dataset]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $deleted = $this->cartoSyncApi->deleteDataset($this->dataset);
    if ($deleted) {
      drupal_set_message($this->t('Dataset @dataset deleted successfully.', ['@dataset' => $this->dataset]));
    }
    else {
      drupal_set_message($this->t('There was an error processing your request.'), 'error');
    }
    $form_state->setRedirect('carto_sync.carto_sync_dashboard');
  }

}
