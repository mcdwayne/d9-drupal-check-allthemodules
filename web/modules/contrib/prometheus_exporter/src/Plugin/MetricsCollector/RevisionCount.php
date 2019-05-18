<?php

namespace Drupal\prometheus_exporter\Plugin\MetricsCollector;

/**
 * Collects metrics for total revision count.
 *
 * @MetricsCollector(
 *   id = "revision_count",
 *   title = @Translation("Revision count"),
 *   description = @Translation("Total revision count.")
 * )
 */
class RevisionCount extends NodeCount {

  /**
   * {@inheritdoc}
   */
  protected function getCountQuery() {
    return parent::getCountQuery()->allRevisions();
  }

}
