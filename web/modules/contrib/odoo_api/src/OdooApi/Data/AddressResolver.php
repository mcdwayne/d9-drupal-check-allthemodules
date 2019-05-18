<?php

namespace Drupal\odoo_api\OdooApi\Data;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\odoo_api\OdooApi\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\odoo_api\OdooApi\Exception\DataException;
use Drupal\odoo_api\OdooApi\Util\ResponseCacheTrait;

/**
 * Class AddressResolver.
 */
class AddressResolver implements AddressResolverInterface {

  use ResponseCacheTrait;

  /**
   * Drupal\odoo_api\OdooApi\ClientInterface definition.
   *
   * @var \Drupal\odoo_api\OdooApi\ClientInterface
   */
  protected $odooApiApiClient;

  /**
   * Constructs a new AddressResolver object.
   */
  public function __construct(ClientInterface $odoo_api_api_client, CacheBackendInterface $cache_service, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->odooApiApiClient = $odoo_api_api_client;
    $this->setCacheOptions($cache_service, $cache_tags_invalidator, 'odoo_api.address_resolver');
  }

  /**
   * {@inheritdoc}
   */
  public function findCountryIdByCode($country_code) {
    $countries = $this->getAllCountriesByCode();
    if (!isset($countries[$country_code]['id'])) {
      throw new DataException((string) new FormattableMarkup('Could not find Odoo country "@code".', ['@code' => $country_code]));
    }
    return $countries[$country_code]['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function findCountryCodeById(int $country_id) {
    $countries = $this->getAllCountriesByCode();

    $countries_id_code = array_column($countries, 'code', 'id');
    if (!isset($countries_id_code[$country_id])) {
      throw new DataException((string) new FormattableMarkup('Could not find country code. Odoo county ID: @code.', ['@code' => $country_id]));
    }
    return $countries_id_code[$country_id];
  }

  /**
   * {@inheritdoc}
   */
  public function findStateIdByCode($country_id, $state_code) {
    $states = $this->getCountryStates($country_id);
    if (!isset($states[$state_code]['id'])) {
      throw new DataException((string) new FormattableMarkup('Could not find Odoo state "@code" for country "@country".', ['@code' => $state_code, '@country' => $country_id]));
    }
    return $states[$state_code]['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function findStateCodeById(int $country_id, int $state_id) {
    $states = $this->getCountryStates($country_id);

    $states_id_code = array_column($states, 'code', 'id');
    if (!isset($states_id_code[$state_id])) {
      throw new DataException((string) new FormattableMarkup('Could not find state code that matches Odoo state ID "@code" for country "@country".', [
        '@code' => $state_id,
        '@country' => $country_id,
      ]));
    }
    return $states_id_code[$state_id];
  }

  /**
   * Get list of countries, keyed by country code.
   *
   * @return mixed
   *   List of all countries, keyed by code.
   */
  protected function getAllCountriesByCode() {
    return $this->cacheResponse('countries_by_code', function () {
      $data = [];
      $fields = [
        'code',
        'id',
        'name',
      ];
      foreach ($this->odooApiApiClient->searchRead('res.country', [], $fields) as $country) {
        $data[$country['code']] = $country;
      }
      return $data;
    });
  }

  /**
   * Get list of states, keyed by state code.
   *
   * @param int $country_id
   *   Odoo country ID.
   *
   * @return mixed
   *   List of all states, keyed by code.
   */
  protected function getCountryStates($country_id) {
    return $this->cacheResponse('country_states:' . $country_id, function () use ($country_id) {
      $data = [];
      $filter = [['country_id', '=', $country_id]];
      $fields = [
        'code',
        'id',
        'name',
      ];
      foreach ($this->odooApiApiClient->searchRead('res.country.state', $filter, $fields) as $state) {
        $data[$state['code']] = $state;
      }
      return $data;
    });
  }

}
