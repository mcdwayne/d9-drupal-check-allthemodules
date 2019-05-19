<?php

namespace Drupal\tmgmt_extension_suit\Plugin\Action;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "tmgmt_extension_suit_clear_job_items_data_action",
 *   label = @Translation("Clear JobItem cache (data property)"),
 *   type = "tmgmt_job",
 *   confirm_form_route_name = "tmgmt_extension_suit.clear_job_items_data_approve_action"
 * )
 */
class ClearJobItemsDataAction extends BaseJobAction {

  /**
   * {@inheritdoc}
   */
  protected function getTempStoreName($entity_type = '') {
    return 'tmgmt_extension_suit_' . $entity_type . '_operations_clear_job_items_data';
  }

}
