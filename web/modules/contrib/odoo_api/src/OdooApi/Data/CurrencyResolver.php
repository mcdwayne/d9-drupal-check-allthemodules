<?php

namespace Drupal\odoo_api\OdooApi\Data;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\odoo_api\OdooApi\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\odoo_api\OdooApi\Exception\DataException;
use Drupal\odoo_api\OdooApi\Util\ResponseCacheTrait;

/**
 * Class CurrencyResolver.
 */
class CurrencyResolver implements CurrencyResolverInterface {

  use ResponseCacheTrait;

  /**
   * Drupal\odoo_api\OdooApi\ClientInterface definition.
   *
   * @var \Drupal\odoo_api\OdooApi\ClientInterface
   */
  protected $odooApiApiClient;

  /**
   * Constructs a new CurrencyResolver object.
   */
  public function __construct(ClientInterface $odoo_api_api_client, CacheBackendInterface $cache_service, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->odooApiApiClient = $odoo_api_api_client;
    $this->setCacheOptions($cache_service, $cache_tags_invalidator, 'odoo_api.currency_resolver');
  }

  /**
   * {@inheritdoc}
   */
  public function findCurrencyIdByCode($currency_code) {
    $currencies = $this->getAllCurrenciesByCode();
    if (!isset($currencies[$currency_code]['id'])) {
      throw new DataException((string) new FormattableMarkup('Could not find Odoo currency "@code".', ['@code' => $currency_code]));
    }
    return $currencies[$currency_code]['id'];
  }

  /**
   * Get list of currencies, keyed by currency code.
   *
   * @return mixed
   *   List of all currencies, keyed by code.
   */
  protected function getAllCurrenciesByCode() {
    $currencies_by_code = [];

    foreach ($this->getAllCurrenciesById() as $currency) {
      $currencies_by_code[$currency['name']] = $currency;
    }

    return $currencies_by_code;
  }

  /**
   * {@inheritdoc}
   */
  public function findCurrencyCodeById($currency_id) {
    $currencies = $this->getAllCurrenciesById();
    if (empty($currencies[$currency_id])) {
      throw new DataException((string) new FormattableMarkup('Could not find Odoo currency "@id".', ['@id' => $currency_id]));
    }
    return $currencies[$currency_id]['name'];
  }

  /**
   * Get list of currencies, keyed by currency id.
   *
   * @return mixed
   *   List of all currencies, keyed by id.
   */
  protected function getAllCurrenciesById() {
    return $this->cacheResponse('currencies_by_id', function () {
      $data = [];
      $fields = ['id', 'name'];
      foreach ($this->odooApiApiClient->searchRead('res.currency', [], $fields) as $currency) {
        $data[$currency['id']] = $currency;
      }
      return $data;
    });
  }

}
