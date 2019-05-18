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
 * @ViewsField("nodeownership_claim_decline_field")
 */
class NodeownershipClaimDeclineField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $claim_id = $this->getValue($values);
    $status = $values->{"nodeownership_status"};
    if ($status == NODEOWNERSHIP_CLAIM_PENDING || $status == NODEOWNERSHIP_CLAIM_APPROVED) {
      $url = CoreUrl::fromRoute('entity.nodeownership_claim.decline', ['nodeownership_claim' => $claim_id]);
      return \Drupal::l($this->t('Decline'), $url);
    }
    return $this->t('Declined');
  }

}
