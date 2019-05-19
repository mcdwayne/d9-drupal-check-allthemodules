<?php

namespace Drupal\tmgmt_extension_suit\Plugin\Action;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "tmgmt_extension_suit_delete_job_action",
 *   label = @Translation("Delete Job"),
 *   type = "tmgmt_job",
 *   confirm_form_route_name = "tmgmt_extension_suit.delete_approve_action"
 * )
 */
class DeleteJobAction extends BaseJobAction {
  protected function getTempStoreName($entity_type = '') {
    return 'tmgmt_extension_suit_' . $entity_type . '_operations_delete';
  }
}
