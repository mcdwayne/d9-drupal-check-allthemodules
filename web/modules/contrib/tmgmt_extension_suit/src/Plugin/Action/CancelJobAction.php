<?php

namespace Drupal\tmgmt_extension_suit\Plugin\Action;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "tmgmt_extension_suit_cancel_job_action",
 *   label = @Translation("Cancel Job"),
 *   type = "tmgmt_job",
 *   confirm_form_route_name = "tmgmt_extension_suit.cancel_approve_action"
 * )
 */
class CancelJobAction extends BaseJobAction {
  protected function getTempStoreName($entity_type = '') {
    return 'tmgmt_extension_suit_' . $entity_type . '_operations_cancel';
  }
}
