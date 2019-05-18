<?php

namespace Drupal\odoo_api_migrate;

/**
 * Odoo Cron migration source interface.
 */
interface OdooCronMigrationSourceInterface {

  /**
   * Enable or disable Cron mode.
   *
   * Enabling it will cause Odoo API client to restrict fetched rows based on
   * highwater field value.
   *
   * @param bool $value
   *   New Cron mode value.
   */
  public function setCronMode($value);

  /**
   * Force Cron import of objects with given IDs.
   *
   * This method may be used to bypass highwater filter used with Cron mode.
   *
   * @param array|int[] $ids
   *   Array of IDs of objects to force import.
   */
  public function forceCronImportObjects($ids);

}
