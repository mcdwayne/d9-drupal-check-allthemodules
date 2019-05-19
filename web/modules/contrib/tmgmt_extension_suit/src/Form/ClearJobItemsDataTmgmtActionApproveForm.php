<?php

namespace Drupal\tmgmt_extension_suit\Form;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class ClearJobItemsDataTmgmtActionApproveForm extends BaseTmgmtActionApproveForm {

  /**
   * Temp storage name we are saving entity_ids to.
   *
   * @var string
   */
  protected $tempStorageName = 'tmgmt_extension_suit_tmgmt_job_operations_clear_job_items_data';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_extension_suit_clear_job_items_data_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear JobItem cache (data property)');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clear JobItem cache (data property) from selected jobs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Clearing job items data can take some time, do not close the browser');
  }

  /**
   * {@inheritdoc}
   */
  public static function processBatch($data, &$context) {
    $job = parent::processBatch($data, $context);

    if (!empty($job)) {
      $context['results']['count']++;

      foreach ($job->getItems() as $job_item) {
        $job_item->resetData();
        $job_item->save();
      }

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
