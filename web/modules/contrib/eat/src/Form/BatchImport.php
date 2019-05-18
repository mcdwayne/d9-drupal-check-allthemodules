<?php

namespace Drupal\eat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eat\Eat;

/**
 * Allows bulk import of entities.
 */
class BatchImport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eat_batch_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Batch import')
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Eat::matchupEntitiesToSet();
    drupal_set_message(t('Content updated to use EAT. Yum Yum.'));
  }

}