<?php

namespace Drupal\perfmon\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PerfmonRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'perfmon_run_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tests = NULL) {
    $form['run_form'] = array(
      '#type' => 'fieldset',
      '#title' => t('Run'),
      '#description' => $this->t('Click the button below to run the performance testlist and review the results.'),
      '#collapsible' => TRUE,
      '#collapsed' => empty($tests) ? FALSE : TRUE,
    );
    $form['run_form']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Run testlist'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $testlist = perfmon_get_testlist();

    // Use Batch to process the testlist.
    $batch = array(
      'operations' => array(),
      'title' => t('Performance test'),
      'progress_message' => t('Progress @current out of @total.'),
      'error_message' => t('An error occurred. Rerun the process or consult the logs.'),
      'finished' => '_perfmon_batch_finished',
    );
    $log = \Drupal::config('perfmon.settings')->get('perfmon_log');

    foreach ($testlist as $test_name => $test) {
      // Each test is its own operation. There could be a case where a single
      // test needs to run itself a batch operation, perhaps @todo?
      $batch['operations'][] = array(
        '_perfmon_batch_op',
        array($test_name, $test, $log),
      );
    }

    batch_set($batch);
  }
}
