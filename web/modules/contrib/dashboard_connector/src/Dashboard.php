<?php

namespace Drupal\dashboard_connector;

/**
 * The Dashboard class.
 */
class Dashboard {

  /**
   * Gets the snapshot builder.
   *
   * @return \Drupal\dashboard_connector\SnapshotBuilderInterface
   *   The snapshot builder.
   */
  public static function snapshotBuilder() {
    return \Drupal::service('dashboard_connector.snapshot_builder');
  }

  /**
   * Gets the dashboard API connector.
   *
   * @return \Drupal\dashboard_connector\DashboardConnectorInterface
   *   The dashboard connector.
   */
  public static function connector() {
    return \Drupal::service('dashboard_connector.connector');
  }

}
