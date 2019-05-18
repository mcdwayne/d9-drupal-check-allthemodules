<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * A handler to display the latest ping status of a domain.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("drd_ping_status")
 */
class PingStatus extends Standard {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->realField = 'id';
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /* @var $domain \Drupal\drd\Entity\Domain */
    $domain = $values->_entity;

    $status = NULL;
    if (is_object($domain)) {
      $status = $domain->getLatestPingStatus(FALSE);
    }

    if (is_null($status)) {
      return $this->t('unknown');
    }
    elseif ($status) {
      return $this->t('ok');
    }
    else {
      return $this->t('failed');
    }
  }

}
