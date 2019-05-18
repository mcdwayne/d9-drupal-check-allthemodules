<?php

namespace Drupal\batch_jobs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\batch_jobs\Job;

/**
 * Defines a confirmation form for deleting a job.
 */
class BatchJobsDelete extends ConfirmFormBase {

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
    return 'batch_jobs_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $job = new Job($this->bid);
    return t('Do you want to delete batch job %title?',
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
   *   The ID of the batch job to be deleted.
   * @param string $token
   *   String token.
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
    Job::delete($this->bid);

    drupal_set_message(t('Batch job has been successfully deleted.'));
    $form_state->setRedirect('batch_jobs.jobs');
  }

}
