<?php

namespace Drupal\devel_debug_log\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DevelDebugLogDeleteForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'ddl_delete_form';
  }

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ddl_clear'] = array(
      '#type' => 'fieldset',
      '#title' => t('Clear debug log messages'),
      '#description' => t('This will permanently remove the log messages from the database.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['ddl_clear']['clear'] = array(
      '#type' => 'submit',
      '#value' => t('Clear log messages'),
    );

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Database::getConnection()->delete('devel_debug_log')
      ->execute();
    drupal_set_message(t('All debug messages have been cleared.'));
  }
}
