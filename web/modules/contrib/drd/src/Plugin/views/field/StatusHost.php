<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\drd\Entity\BaseInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_host_status_agg")
 */
class StatusHost extends StatusBase {

  /**
   * {@inheritdoc}
   */
  public function getDomains(BaseInterface $remote) {
    /** @var \Drupal\drd\Entity\HostInterface $remote */
    return $remote->getDomains();
  }

}
