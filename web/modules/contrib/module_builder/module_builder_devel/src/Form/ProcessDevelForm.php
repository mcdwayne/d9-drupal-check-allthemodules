<?php

namespace Drupal\module_builder_devel\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for running selected analysis jobs and outputting the result.
 *
 * This does not save the resulting analysis data to storage.
 */
class ProcessDevelForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_builder_devel_process_form';
  }

  /**
   * Form constructor.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    \Drupal::service('module_builder.drupal_code_builder')->loadLibrary();

    try {
      $task_handler_report = \Drupal::service('module_builder.drupal_code_builder')->getTask('ReportHookDataFolder');
      $task_report_summary = \Drupal::service('module_builder.drupal_code_builder')->getTask('ReportSummary');
      $task_handler_collect = \Drupal::service('module_builder.drupal_code_builder')->getTask('Collect');
    }
    catch (SanityException $e) {
      // We're in right place to do something about a problem, so no need to
      // show a message.
    }

    $job_list = $this->getJobList($form_state);

    $job_options = [];
    foreach ($job_list as $index => $job) {
      $option_label = isset($job['item_label']) ?
        "{$job['process_label']} - {$job['item_label']}" :
        "{$job['process_label']}";
      $job_options[$index] = $option_label;
    }

    $form['jobs'] = [
      '#type' => 'checkboxes',
      '#title' => t('Jobs to process'),
      '#options' => $job_options,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run selected processing jobs'),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $job_list = $this->getJobList($form_state);

    // Need to filter by stringity, as there is a 0 key which gets a value of
    // "0" if selected and 0 if not.
    $job_indexes_to_run = array_filter($form_state->getValues()['jobs'], function($item) {
      return is_string($item);
    });

    $jobs_to_run = array_intersect_key($job_list, $job_indexes_to_run);

    $result = [];

    // Hack into the Collect task handler so we can get the collector helper.
    // This is going around DCB's API!
    $task_handler_collect = \Drupal::service('module_builder.drupal_code_builder')->getTask('Collect');
    $get_collector_helper_function = function($collector_type) {
      return $this->getHelper($collector_type);
    };
    $get_collector_helper = \Closure::bind($get_collector_helper_function, $task_handler_collect, get_class($task_handler_collect));

    foreach ($jobs_to_run as $job) {
      $collector_helper = $get_collector_helper($job['collector']);
      $job_data = $collector_helper->collect([$job]);

      dpm($job_data);
    }

    // Keep the selected values in the form.
    $form_state->setRebuild();
  }

  /**
   * Gets the job list from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return
   *   The job list array.
   */
  protected function getJobList(FormStateInterface $form_state) {
    $job_list = $form_state->get('job_list');

    if (!isset($job_list)) {
      $task_handler_collect = \Drupal::service('module_builder.drupal_code_builder')->getTask('Collect');
      $job_list = $task_handler_collect->getJobList();

      $form_state->set('job_list', $job_list);
    }

    return $job_list;
  }

}
