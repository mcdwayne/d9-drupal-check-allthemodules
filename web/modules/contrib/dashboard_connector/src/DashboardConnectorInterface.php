<?php

namespace Drupal\dashboard_connector;

/**
 * Interface for the dashboard connector.
 */
interface DashboardConnectorInterface {

  /**
   * Sends the snapshot of checks.
   *
   * @param array $snapshot
   *   The snapshot of checks.
   */
  public function sendSnapshot(array $snapshot);

}
