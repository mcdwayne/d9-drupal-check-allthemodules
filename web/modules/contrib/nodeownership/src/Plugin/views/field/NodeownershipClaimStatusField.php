<?php

namespace Drupal\nodeownership\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to render the claim status.
 *
 * @ingroup nodeownership
 *
 * @ViewsField("nodeownership_claim_status_field")
 */
class NodeownershipClaimStatusField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $status_map = array(
      0 => $this->t('Pending'),
      1 => $this->t('Approved'),
      2 => $this->t('Declined'),
    );

    return $status_map[$value];

  }

}
