<?php

namespace Drupal\commerce_xem\EventSubscriber;

use Drupal\commerce_currency_resolver\ExchangeRateEventSubscriberBase;
use Drupal\commerce_currency_resolver\CurrencyHelper;

/**
 * Class ExchangeRateCoinMarketCap.
 */
class ExchangeRateCoinMarketCap extends ExchangeRateEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function apiUrl() {
    return 'https://api.coinmarketcap.com/v2';
  }

  /**
   * {@inheritdoc}
   */
  public static function sourceId() {
    return 'exchange_rate_coinmarketcap';
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalData($baseCoinSymbol = NULL) {
    $data = NULL;

    // Prepare for client.
    $url = self::apiUrl();
    $client = \Drupal::httpClient();
    $response =  
        $client->request('GET', $url . '/listings');

    if ($response->getBody()) {
      $allCoins = json_decode($response->getBody());
    }
    
    $coinMarketObject = NULL;
    foreach($allCoins->data as $coin) {
      if ($coin->symbol == $baseCoinSymbol) {
        $coinMarketObject = $coin;
      }
    }

    $conversionResponse = $client->request('GET', $url . '/ticker/' . $coinMarketObject->id . '/', [
      'query' => [
        'convert' => 'USD'
      ]
    ]);
    
    if ($conversionResponse->getBody()) {
      $conversionData = json_decode($conversionResponse->getBody());
    }
    
    $data = [];
    if (!empty($conversionData->data->quotes)) {
      foreach($conversionData->data->quotes as $currencyCode => $quote) {
        $data[$currencyCode] = $quote->price;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefaultCurrency() {
    $exchange_rates = [];

    // Default currency.
    $currency_default = \Drupal::config('commerce_currency_resolver.settings')
      ->get('currency_default');

    $data = $this->getExternalData($currency_default);

    if ($data) {
      $process = $this->reverseCalculate($currency_default, 'USD', $data);
      // Get data
      $exchange_rates = $this->mapExchangeRates($process, $currency_default);
    }
    return $exchange_rates;
  }

  /**
   * {@inheritdoc}
   */
  public function processAllCurrencies() {
    $exchange_rates = [];

    // Enabled currency.
    $enabled = CurrencyHelper::getEnabledCurrency();

    foreach ($enabled as $base => $name) {
      // Foreach enabled currency fetch others.
      $data = $this->getExternalData($base);

      if ($data) {
        $get_rates = $this->mapExchangeRates($data, $base);
        $exchange_rates[$base] = $get_rates[$base];
      }
    }

    return $exchange_rates;
  }

}
