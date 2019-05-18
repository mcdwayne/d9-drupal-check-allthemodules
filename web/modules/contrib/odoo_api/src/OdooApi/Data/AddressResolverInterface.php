<?php

namespace Drupal\odoo_api\OdooApi\Data;

/**
 * Address resolver service interface.
 */
interface AddressResolverInterface {

  /**
   * Find country ID by code.
   *
   * @param string $country_code
   *   Country code string, e.g. 'US'.
   *
   * @return int
   *   Odoo country ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\DataException
   *   No such country.
   */
  public function findCountryIdByCode($country_code);

  /**
   * Find country code by Odoo country ID.
   *
   * @param int $country_id
   *   Odoo country ID, ex: 223.
   *
   * @return string
   *   Country code (Ex: US).
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\DataException
   *   No such country.
   */
  public function findCountryCodeById(int $country_id);

  /**
   * Find state ID by code.
   *
   * @param int $country_id
   *   Country ID, as returned by findCountryIdByCode().
   * @param string $state_code
   *   Country code, e.g. 'FL' for Florida.
   *
   * @return int
   *   Odoo state ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\DataException
   *   No such state.
   */
  public function findStateIdByCode($country_id, $state_code);

  /**
   * Find state code by ID.
   *
   * @param int $country_id
   *   Country ID, as returned by findCountryIdByCode().
   * @param int $state_id
   *   Odoo state ID.
   *
   * @return string
   *   Odoo state code.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\DataException
   *   No such state.
   */
  public function findStateCodeById(int $country_id, int $state_id);

  /**
   * Invalidate API responses cache.
   */
  public function invalidateCache();

}
