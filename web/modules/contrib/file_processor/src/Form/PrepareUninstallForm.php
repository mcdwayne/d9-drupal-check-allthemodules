<?php

namespace Drupal\file_processor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PrepareUninstallForm.
 *
 * @package Drupal\file_processor\Form
 */
class PrepareUninstallForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_processor_prepare_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file_processor'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Prepare uninstall'),
      '#description' => $this->t('Clicking on this button, all File Processor data will be removed.'),
    );

    $form['file_processor']['prepare_uninstall'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete File Processor data'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $num_updates = db_update('file_managed')
      ->fields(array(
        'process' => NULL,
      ))
      ->execute();

    drupal_set_message($this->t('Deleted all file processor data.'));
  }
}
