<?php

namespace Drupal\odoo_api\OdooApi\Data;

/**
 * Currency resolver service interface.
 */
interface CurrencyResolverInterface {

  /**
   * Find currency ID by code.
   *
   * @param string $currency_code
   *   Currency code string, e.g. 'USD'.
   *
   * @return int
   *   Odoo currency ID.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\DataException
   *   No such currency.
   */
  public function findCurrencyIdByCode($currency_code);

  /**
   * Invalidate API responses cache.
   */
  public function invalidateCache();

  /**
   * Finds a currency by the currency id.
   *
   * @param int $currency_id
   *   The Odoo currency id.
   *
   * @return array
   *   The currency.
   *
   * @throws \Drupal\odoo_api\OdooApi\Exception\DataException
   *   No such currency.
   */
  public function findCurrencyCodeById($currency_id);

}
