<?php

namespace Drupal\commerce_currency_resolver\EventSubscriber;

use Drupal\commerce_currency_resolver\ExchangeRateEventSubscriberBase;

/**
 * Class ExchangeRateFixer.
 */
class ExchangeRateFixer extends ExchangeRateEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function apiUrl() {
    return 'http://data.fixer.io/api/latest';
  }

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'exchange_rate_fixer';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalData($base_currency = NULL) {
    $data = NULL;

    // Prepare for client.
    $url = self::apiUrl();
    $method = 'GET';
    $options = [
      'query' => ['access_key' => self::apiKey()],
    ];

    // Add base currency.
    if (!empty($base_currency)) {
      $options['query']['base'] = $base_currency;
    }

    $request = $this->apiClient($method, $url, $options);

    if ($request) {
      $json = json_decode($request);

      if ($json->success) {
        // Leave base currency. In some cases we don't know base currency.
        // Fixer.io on free plan uses your address for base currency, and in
        // Drupal you could have different default value.
        $data['base'] = $json->base;

        // Loop and build array.
        foreach ($json->rates as $key => $value) {
          $data['rates'][$key] = $value;
        }

      }

      else {
        \Drupal::logger('commerce_currency_resolver')->debug($json->error->info);
        return FALSE;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function processCurrencies() {
    // Currency is tied to your account on Fixer.io, so we need to be sure
    // to get correct data. You cannot choose base currency on free
    // account. For this reason calculation for all currencies is same
    // even if you using cross sync or not.
    $exchange_rates = [];

    // Foreach enabled currency fetch others.
    $data = $this->getExternalData();

    if ($data) {
      // Currency is tied to your account on Fixer.io, so we need to be sure
      // to get correct data.
      $exchange_rates = $this->crossSyncCalculate($data['base'], $data['rates']);

    }

    return $exchange_rates;
  }

}
