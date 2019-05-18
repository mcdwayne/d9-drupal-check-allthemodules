<?php

namespace Drupal\kpi_analytics;

/**
 * Interface KPIBuilderInterface.
 *
 * @package Drupal\kpi_analytics
 */
interface KPIBuilderInterface {

  /**
   * Lazy builder callback for displaying a kpi analytics.
   *
   * @return array
   *   A render array for the action link, empty if the user does not have
   *   access.
   */
  public function build($entity_type_id, $entity_id);
}
