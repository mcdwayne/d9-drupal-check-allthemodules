<?php

namespace Drupal\drush_config_import_log\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class RunCustomImport extends FormBase {
   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'run_custom_config_import';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $form['log_file'] = array(
        '#type' => 'markup',
        '#markup' => '<a href="/sites/default/files/custom_drush_config_import_log.txt">check Log File</a>',
      );
      $form['run_import'] = array(
        '#type' => 'submit',
        '#value' => 'Run Import',
      );
      return $form;
  }
  function validateForm(array &$form, FormStateInterface $form_state) {
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $location = \Drupal::config('drush_config_import_log.settings')->get('drush_location', '/usr/local/bin/drush');
    exec($location.' custom-config-import -y');
  }
}