<?php
/**
 * Contains GoogleCurrencyConverter.php.
 */

namespace Drupal\google_scc\CurrencyConverter;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\simple_currency_converter\CurrencyConverter\CurrencyConverterInterface;

class GoogleCurrencyConverter implements CurrencyConverterInterface {

  /**
   * Provides HTTP client service
   */
  protected $httpClient;

  /**
   * Constructs a new object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * @inheritdoc
   */
  public function convert($from_currency, $to_currency, $amount) {
    $output = NULL;

    try {
      $url = "https://finance.google.com/finance/converter?a=$amount&from=$from_currency&to=$to_currency";

      $request = $this->httpClient->get($url);

      $data = $request->getBody();

      $data = explode('bld>', $data);
      $data = explode($to_currency, $data[1]);

      $output = trim($data[0]);
      $output = round($output, 5);
    }
    catch (RequestException $e) {

    }

    return $output;
  }

  /**
   * @inheritdoc
   */
  public function currencies() {
    return [
      'AED',
      'AFN',
      'ANG',
      'AOA',
      'ARS',
      'AUD',
      'AWG',
      'AZN',
      'BAM',
      'BBD',
      'BDT',
      'BGN',
      'BHD',
      'BIF',
      'BMD',
      'BND',
      'BOB',
      'BRL',
      'BSD',
      'BTN',
      'BWP',
      'BYR',
      'BZD',
      'CAD',
      'CDF',
      'CHF',
      'CLP',
      'CNY',
      'COP',
      'CRC',
      'CUP',
      'CVE',
      'CZK',
      'DJF',
      'DKK',
      'DOP',
      'DZD',
      'EGP',
      'ERN',
      'ETB',
      'EUR',
      'FJD',
      'FKP',
      'GBP',
      'GHS',
      'GIP',
      'GMD',
      'GNF',
      'GTQ',
      'GYD',
      'HKD',
      'HNL',
      'HRK',
      'HTG',
      'HUF',
      'IDR',
      'ILS',
      'INR',
      'IRR',
      'ISK',
      'JMD',
      'JOD',
      'JPY',
      'KES',
      'KGS',
      'KMF',
      'KRW',
      'KWD',
      'KYD',
      'KZT',
      'LAK',
      'LBP',
      'LKR',
      'LRD',
      'LSL',
      'LTL',
      'LVL',
      'LYD',
      'MAD',
      'MDL',
      'MKD',
      'MMK',
      'MNT',
      'MOP',
      'MRO',
      'MUR',
      'MXN',
      'MYR',
      'MZN',
      'NAD',
      'NGN',
      'NIO',
      'NOK',
      'NPR',
      'NZD',
      'PAB',
      'PEN',
      'PGK',
      'PHP',
      'PKR',
      'PLN',
      'PYG',
      'QAR',
      'RON',
      'RSD',
      'RUB',
      'SAR',
      'SBD',
      'SCR',
      'SEK',
      'SGD',
      'SHP',
      'SLL',
      'SOS',
      'SRD',
      'STD',
      'SYP',
      'SZL',
      'THB',
      'TND',
      'TOP',
      'TRY',
      'TTD',
      'TWD',
      'TZS',
      'UAH',
      'UGX',
      'USD',
      'UYU',
      'VEF',
      'VND',
      'VUV',
      'WST',
      'XAF',
      'XCD',
      'XOF',
      'XPF',
      'YER',
      'ZAR',
      'ZMK',
    ];
  }

}
