<?php

namespace Drupal\tmgmt_memory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the memory export form.
 */
class ExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_memory_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['label'] = array(
      '#type' => 'item',
      '#plain_text' => t('Export and download all the translations as a gzipped tar file.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('tmgmt_memory.export_download');
  }

}
