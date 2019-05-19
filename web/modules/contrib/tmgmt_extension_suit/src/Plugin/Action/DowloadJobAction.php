<?php

namespace Drupal\tmgmt_extension_suit\Plugin\Action;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "tmgmt_extension_suit_download_job_action",
 *   label = @Translation("Download Translation"),
 *   type = "tmgmt_job",
 *   confirm_form_route_name = "tmgmt_extension_suit.download_approve_action"
 * )
 */
class DowloadJobAction extends BaseJobAction {
  protected function getTempStoreName($entity_type = '') {
    return 'tmgmt_extension_suit_' . $entity_type . '_operations_download';
  }
}
