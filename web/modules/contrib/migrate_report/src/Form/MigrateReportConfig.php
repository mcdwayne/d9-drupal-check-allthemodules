<?php

namespace Drupal\migrate_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the migrate report configurations.
 */
class MigrateReportConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_report.config';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['migrate_report.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_report.config');

    $form['report_dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reports directory'),
      '#description' => $this->t('The directory location where the reports are generated. It should be writable by the migration runner. A stream wrapper such as public://reports can be used too.'),
      '#default_value' => $config->get('report_dir'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $report_dir = $form_state->getValue('report_dir');
    if (!file_prepare_directory($report_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $form_state->setErrorByName('report_dir', $this->t("Directory %dir doesn't exist or is not writable.", ['%dir' => $report_dir]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('migrate_report.config')
      ->set('report_dir', $form_state->getValue('report_dir'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
