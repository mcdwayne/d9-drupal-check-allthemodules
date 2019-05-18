<?php

namespace Drupal\devel_mail_logger\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DevelMailLoggerDeleteForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'devel_mail_logger';
  }

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['dml_clear'] = array(
      '#type' => 'fieldset',
      '#title' => t('Delete debug mails'),
      '#description' => t('This will permanently remove the debug mails from the database.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['dml_clear']['clear'] = array(
      '#type' => 'submit',
      '#value' => t('Delete debug mails'),
    );

    $form['dml_clear']['send'] = array(
      '#type' => 'link',
      '#value' => t('Send test mail'),
      '#title' => t('Send test mail'),
      '#url' => \Drupal\Core\Url::fromRoute('devel_mail_logger.send'),
    );

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Database::getConnection()->delete('devel_mail_logger')
      ->execute();
    drupal_set_message(t('All Mails have been deleted.'));
  }
}
