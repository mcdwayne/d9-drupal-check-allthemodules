<?php

namespace Drupal\tmgmt_extension_suit\Form;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class DeleteTmgmtActionApproveForm extends BaseTmgmtActionApproveForm {

  /**
   * Temp storage name we are saving entity_ids to.
   *
   * @var string
   */
  protected $tempStorageName = 'tmgmt_extension_suit_tmgmt_job_operations_delete';

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Jobs');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_extension_suit_delete_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete these jobs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting can take some time, do not close the browser');
  }

  /**
   * {@inheritdoc}
   */
  public static function processBatch($data, &$context) {
    $job = parent::processBatch($data, $context);

    if (!empty($job)) {
      $job->delete();
      $context['results']['count']++;

      $context['message'] = new FormattableMarkup('Processed %name.', [
        '%name' => $job->label(),
      ]);
    }
    else {
      $context['message'] = new FormattableMarkup('Skipped %name.', [
        '%name' => $data['entity_type'],
      ]);
    }
  }
}
