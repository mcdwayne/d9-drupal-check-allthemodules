<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\drd\Entity\BaseInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_core_status_agg")
 */
class StatusCore extends StatusBase {

  /**
   * {@inheritdoc}
   */
  public function getDomains(BaseInterface $remote) {
    /** @var \Drupal\drd\Entity\CoreInterface $remote */
    return $remote->getDomains();
  }

}
