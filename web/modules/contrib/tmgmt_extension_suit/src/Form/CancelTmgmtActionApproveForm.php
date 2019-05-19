<?php

namespace Drupal\tmgmt_extension_suit\Form;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class CancelTmgmtActionApproveForm extends BaseTmgmtActionApproveForm {

  /**
   * Temp storage name we are saving entity_ids to.
   *
   * @var string
   */
  protected $tempStorageName = 'tmgmt_extension_suit_tmgmt_job_operations_cancel';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_extension_suit_cancel_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel Jobs');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel these jobs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Canceling can take some time, do not close the browser');
  }

  /**
   * {@inheritdoc}
   */
  public static function processBatch($data, &$context) {
    $job = parent::processBatch($data, $context);

    if (!empty($job) && !$job->abortTranslation()) {
      $context['results']['count']++;
      // This is the case when a translator does not support the abort
      // operation.
      // It would make more sense to not display the button for the action,
      // however we do not know if the translator is able to abort a job until
      // we trigger the action.
      foreach ($job->getMessagesSince() as $message) {
        /** @var \Drupal\tmgmt\MessageInterface $message */
        if ($message->getType() == 'debug') {
          continue;
        }

        if ($text = $message->getMessage()) {
          // We want to persist also the type therefore we will set the
          // messages directly and not return them.
          drupal_set_message($text, $message->getType());
        }
      }
    }
    else {
      $context['results']['errors'][] = new FormattableMarkup('Error aborting %name', [
        '%name' => $job->label(),
      ]);

      return;
    }

    $context['message'] = new FormattableMarkup('Processed %name.', [
      '%name' => $job->label(),
    ]);

  }
}
