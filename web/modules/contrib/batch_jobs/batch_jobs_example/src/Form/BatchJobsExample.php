<?php

namespace Drupal\batch_jobs_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Batch jobs example.
 */
class BatchJobsExample extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_jobs_example';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['number'] = [
      '#type' => 'select',
      '#title' => t('Number of tasks to generate'),
      '#options' => [
        1 => 1,
        10 => 10,
        50 => 50,
        100 => 100,
        500 => 500,
      ],
      '#default_value' => 10,
    ];

    $form['autorun'] = [
      '#type' => 'checkbox',
      '#title' => t('Autorun'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create batch job'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $batch = batch_jobs_example_create_job($values['number']);
    if ($values['autorun']) {
      $route_parameters = [
        'bid' => $batch->bid,
        'token' => $batch->getToken(),
      ];
      $form_state->setRedirect('batch_jobs.run', $route_parameters);
    }
    else {
      drupal_set_message(t('Created new batch job'));
      $form_state->setRedirect('batch_jobs.jobs');
    }
  }

}
