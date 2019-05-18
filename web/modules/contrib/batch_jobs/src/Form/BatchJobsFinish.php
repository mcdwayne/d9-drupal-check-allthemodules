<?php

namespace Drupal\batch_jobs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\batch_jobs\Job;

/**
 * Defines a confirmation form for finishing a job.
 */
class BatchJobsFinish extends ConfirmFormBase {

  /**
   * The ID of the batch job.
   *
   * @var int
   */
  protected $bid;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_jobs_finish_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $job = new Job($this->bid);
    return t('Do you want to run the finish tasks for batch job %title?',
      ['%title' => $job->title]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('batch_jobs.jobs');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param int $bid
   *   The ID of the batch job to finish.
   * @param string $token
   *   Token string.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bid = NULL, $token = NULL) {

    $job = new Job($bid);
    if (!$job->access($token)) {
      return [];
    }

    $this->bid = $bid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $job = new Job($this->bid);

    $result = $job->finish();

    if ($result) {
      drupal_set_message(t('Finish tasks for the batch job have been run.'));
    }
    else {
      drupal_set_message(t('Finish tasks for the batch job failed to run.'),
        'error');
    }
    $form_state->setRedirect('batch_jobs.jobs');
  }

}
