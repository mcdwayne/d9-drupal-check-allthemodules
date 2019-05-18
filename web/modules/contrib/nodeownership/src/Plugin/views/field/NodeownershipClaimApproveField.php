<?php

namespace Drupal\nodeownership\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url as CoreUrl;

/**
 * Field handler to render the claim status.
 *
 * @ingroup nodeownership
 *
 * @ViewsField("nodeownership_claim_approve_field")
 */
class NodeownershipClaimApproveField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $claim_id = $this->getValue($values);
    $status = $values->{"nodeownership_status"};
    if ($status == NODEOWNERSHIP_CLAIM_PENDING || $status == NODEOWNERSHIP_CLAIM_DECLINED) {
      $url = CoreUrl::fromRoute('entity.nodeownership_claim.approve', ['nodeownership_claim' => $claim_id]);
      return \Drupal::l($this->t('Approve'), $url);
    }
    return $this->t('Approved');
  }

}
