<?php

namespace Drupal\odoo_api_migrate;

/**
 * Odoo highwater source interface.
 */
interface OdooHighwaterSourceInterface {

  /**
   * Get highwater property value formatter for Odoo API.
   *
   * This method may return values in different format.
   *
   * @return int|string|null
   *   Odoo highwater field value or NULL.
   */
  public function getOdooHighWaterValue();

}
