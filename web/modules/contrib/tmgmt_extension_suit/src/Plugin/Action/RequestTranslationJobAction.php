<?php

namespace Drupal\tmgmt_extension_suit\Plugin\Action;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "tmgmt_extension_suit_request_translation_job_action",
 *   label = @Translation("Request Translation"),
 *   type = "tmgmt_job",
 *   confirm_form_route_name = "tmgmt_extension_suit.request_translation_approve_action"
 * )
 */
class RequestTranslationJobAction extends BaseJobAction {
  protected function getTempStoreName($entity_type = '') {
    return 'tmgmt_extension_suit_' . $entity_type . '_operations_request_translation';
  }
}
